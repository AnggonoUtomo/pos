<?php

namespace App\Modules\Purchasing\PurchaseOrders\Infrastructure\Persistence\Repositories;

use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\PurchaseOrderRepository;
use App\Modules\Purchasing\PurchaseOrders\Infrastructure\Persistence\Models\PurchaseOrder;
use Brick\Math\BigDecimal;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

class EloquentPurchaseOrderRepository implements PurchaseOrderRepository
{
    private function requireTransaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Mutation/lock memerlukan transaction.');
        }
    }

    public function lock(string $id, bool $deleted = false): array
    {
        $this->requireTransaction();

        return ($deleted ? PurchaseOrder::onlyTrashed() : PurchaseOrder::query())->lockForUpdate()->findOrFail($id)->toArray();
    }

    public function save(array $header, array $lines, ?string $id = null): string
    {
        $this->requireTransaction();
        $model = $id === null ? new PurchaseOrder : PurchaseOrder::query()->lockForUpdate()->findOrFail($id);
        $model->fill($header);
        if ($id !== null && ! $model->isDirty() && $this->existingLines($id) === $lines) {
            return $id;
        }
        if ($id === null) {
            $id = strtolower((string) Str::ulid());
            $model->fill(['id' => $id, 'number' => 'PO-'.strtoupper($id), 'status' => 'draft', 'version' => 1]);
            $event = 'created';
        } else {
            $model->version++;
            $event = 'updated';
        }
        if (! $model->save()) {
            throw new RuntimeException('Draft pembelian gagal disimpan.');
        }
        DB::table('purchase_order_lines')->where('purchase_order_id', $id)->delete();
        foreach ($lines as $line) {
            DB::table('purchase_order_lines')->insert($line + ['id' => strtolower((string) Str::ulid()), 'purchase_order_id' => $id]);
        }
        $this->audit($model, $event);

        return $id;
    }

    private function existingLines(string $id): array
    {
        return DB::table('purchase_order_lines')->where('purchase_order_id', $id)->orderBy('item_id')->lockForUpdate()->get()->map(function ($row): array {
            return Arr::except($this->normalizeLine((array) $row), ['id', 'purchase_order_id']);
        })->all();
    }

    public function delete(string $id): void
    {
        $this->requireTransaction();
        $model = PurchaseOrder::query()->lockForUpdate()->findOrFail($id);
        $model->version++;
        if (! $model->save() || ! $model->delete()) {
            throw new RuntimeException('Draft gagal dihapus.');
        }
        $this->audit($model, 'deleted');
    }

    public function restore(string $id): void
    {
        $this->requireTransaction();
        $model = PurchaseOrder::onlyTrashed()->lockForUpdate()->findOrFail($id);
        $model->version++;
        if (! $model->restore()) {
            throw new RuntimeException('Draft gagal dipulihkan.');
        }
        $this->audit($model, 'restored');
    }

    private function audit(PurchaseOrder $model, string $event): void
    {
        $log = activity('purchasing.purchase-orders')->performedOn($model)->event($event)
            ->withProperties(['attributes' => Arr::only($model->toArray(), ['number', 'status', 'version', 'supplier_id', 'warehouse_id', 'total_value']),
                'line_count' => DB::table('purchase_order_lines')->where('purchase_order_id', $model->id)->count()])->log('Draft pembelian '.$event);
        if ($log === null || ! $log->exists) {
            throw new RuntimeException('Audit draft pembelian gagal disimpan.');
        }
    }

    public function paginate(int $page, array $filters): array
    {
        $query = ! empty($filters['trashed']) ? PurchaseOrder::onlyTrashed() : PurchaseOrder::query();
        foreach (['warehouse_id', 'supplier_id'] as $key) {
            $query->when($filters[$key] ?? '', fn ($q, $id) => $q->where($key, $id));
        }
        if (($search = $filters['search'] ?? '') !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(fn ($q) => $q->whereRaw("number LIKE ? ESCAPE '!'", [$like])
                ->orWhereRaw("supplier_name LIKE ? ESCAPE '!'", [$like])->orWhereRaw("warehouse_name LIKE ? ESCAPE '!'", [$like]));
        }
        $result = $query->orderByDesc('id')->paginate(25, page: $page);
        $lines = DB::table('purchase_order_lines')->whereIn('purchase_order_id', $result->getCollection()->pluck('id'))->orderBy('item_id')->get()->groupBy('purchase_order_id');
        $data = $result->getCollection()->map(fn ($model) => $model->toArray() + ['lines' => ($lines[$model->id] ?? collect())->map(fn ($row) => $this->normalizeLine((array) $row))->all()])->all();

        return ['data' => $data, 'meta' => ['current_page' => $result->currentPage(), 'last_page' => $result->lastPage(), 'per_page' => 25, 'total' => $result->total()]];
    }

    private function normalizeLine(array $row): array
    {
        foreach (['quantity', 'conversion_ratio', 'unit_cost', 'base_quantity', 'total_value'] as $field) {
            $row[$field] = (string) BigDecimal::of($row[$field])->toScale(6);
        }

        return $row;
    }

    public function demoExists(string $marker): bool
    {
        return PurchaseOrder::withTrashed()->where('demo_key', $marker)->exists();
    }
}
