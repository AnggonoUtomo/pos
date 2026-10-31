<?php

namespace App\Modules\Inventory\Stock\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Stock\Application\Contracts\MinimumStockAlerts;
use App\Modules\Inventory\Stock\Application\Contracts\MinimumStockRepository;
use App\Modules\Inventory\Stock\Domain\ValueObjects\MinimumStockState;
use App\Modules\Inventory\Stock\Infrastructure\Persistence\Models\StockMinimum;
use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePagination;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DatabaseMinimumStockRepository implements MinimumStockAlerts, MinimumStockRepository
{
    public function __construct(private TablePagination $pagination) {}

    public function find(string $id, bool $lock = false): array
    {
        return StockMinimum::withTrashed()->whereKey(strtolower($id))->when($lock, fn ($q) => $q->lockForUpdate())->firstOrFail()->toArray();
    }

    public function pairExists(string $warehouseId, string $itemId): bool
    {
        return StockMinimum::withTrashed()->where('warehouse_id', $warehouseId)->where('item_id', $itemId)->lockForUpdate()->first() !== null;
    }

    public function insert(array $record, int $actorId): string
    {
        $model = StockMinimum::create($record);
        if (! $model->exists) {
            throw new RuntimeException('Pengaturan stok minimum tidak tersimpan.');
        }
        $this->audit($model, 'created', [], $actorId);

        return $model->id;
    }

    public function change(string $id, array $changes, string $event, int $actorId): void
    {
        $model = StockMinimum::withTrashed()->lockForUpdate()->findOrFail($id);
        $before = $model->only(['minimum_quantity', 'is_active', 'deleted_at', 'version']);
        if (! $model->update($changes)) {
            throw new RuntimeException('Pengaturan stok minimum tidak tersimpan.');
        }
        $this->audit($model, $event, $before, $actorId);
    }

    private function audit(StockMinimum $model, string $event, array $before, int $actorId): void
    {
        $log = activity('inventory.stock')->performedOn($model)->event('minimum.'.$event)->causedBy($actorId)
            ->withProperties(['old' => $before, 'attributes' => $model->only(['warehouse_id', 'item_id', 'minimum_quantity', 'is_active', 'deleted_at', 'version'])])->log('Stok minimum '.$event);
        if ($log === null || ! $log->exists) {
            throw new RuntimeException('Audit stok minimum tidak tersimpan.');
        }
    }

    private function rows(bool $trashed = false)
    {
        return DB::table('stock_minimums as m')->join('items as i', 'i.id', '=', 'm.item_id')
            ->join('warehouses as w', 'w.id', '=', 'm.warehouse_id')->join('units as u', 'u.id', '=', 'm.base_unit_id')
            ->leftJoin('stock_balances as b', fn ($join) => $join->on('b.item_id', '=', 'm.item_id')->on('b.warehouse_id', '=', 'm.warehouse_id'))
            ->when($trashed, fn ($q) => $q->whereNotNull('m.deleted_at'), fn ($q) => $q->whereNull('m.deleted_at'))
            ->select('m.*', 'i.sku', 'i.name as item_name', 'w.name as warehouse_name', 'u.name as unit_name')
            ->selectRaw('COALESCE(b.quantity, 0) as quantity')
            ->selectRaw('CASE WHEN i.is_active = 1 AND w.is_active = 1 AND u.is_active = 1 AND i.deleted_at IS NULL AND w.deleted_at IS NULL AND u.deleted_at IS NULL THEN 1 ELSE 0 END as eligible')
            ->orderBy('i.sku')->orderBy('w.code')->orderBy('m.id');
    }

    private function project(object $row): array
    {
        $record = (array) $row;
        $record['is_active'] = (bool) $record['is_active'];
        $record['eligible'] = (bool) $record['eligible'];
        $state = MinimumStockState::evaluate((string) $record['quantity'], (string) $record['minimum_quantity'],
            $record['is_active'] && $record['eligible'] && $record['deleted_at'] === null);
        $record['status'] = ! $record['eligible'] ? 'unavailable' : (! $record['is_active'] ? 'disabled' : ($state['is_low'] ? 'low' : 'healthy'));

        return array_replace($record, $state);
    }

    public function summary(): array
    {
        $low = $empty = 0;
        foreach ($this->rows()->where('m.is_active', true)->cursor() as $row) {
            $state = $this->project($row);
            $low += (int) $state['is_low'];
            $empty += (int) $state['is_empty'];
        }

        return ['low_count' => $low, 'empty_count' => $empty];
    }

    public function paginate(array $filters, int $page): array
    {
        $query = $this->rows($filters['trashed']);
        if ($filters['warehouse_id'] !== '') {
            $query->where('m.warehouse_id', $filters['warehouse_id']);
        }
        if ($filters['search'] !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['search']).'%';
            $query->where(fn ($q) => $q->whereRaw("i.sku LIKE ? ESCAPE '!'", [$like])->orWhereRaw("i.name LIKE ? ESCAPE '!'", [$like])->orWhereRaw("w.name LIKE ? ESCAPE '!'", [$like]));
        }
        // Exact comparison before pagination also works with SQLite string decimals.
        $perPage = $this->pagination->perPage();
        $total = 0;
        $data = [];
        $low = $empty = 0;
        foreach ($query->cursor() as $row) {
            $record = $this->project($row);
            if ($filters['status'] !== 'all' && $record['status'] !== $filters['status']) {
                continue;
            }
            $low += (int) $record['is_low'];
            $empty += (int) $record['is_empty'];
            if ($total >= ($page - 1) * $perPage && count($data) < $perPage) {
                $data[] = $record;
            }
            $total++;
        }

        return ['data' => $data, 'meta' => ['current_page' => $page, 'last_page' => max(1, (int) ceil($total / $perPage)), 'per_page' => $perPage, 'total' => $total],
            'summary' => ['low_count' => $low, 'empty_count' => $empty]];
    }
}
