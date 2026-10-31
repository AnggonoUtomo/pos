<?php

namespace App\Modules\Inventory\Stock\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Stock\Application\Contracts\StockCountRepository;
use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePagination;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DatabaseStockCountRepository implements StockCountRepository
{
    public function __construct(private TablePagination $pagination) {}

    public function active(string $warehouseId): ?array
    {
        $row = DB::table('stock_counts')->where('active_warehouse_id', $warehouseId)->lockForUpdate()->first();

        return $row ? (array) $row : null;
    }

    public function find(string $id, bool $lock = false): array
    {
        $row = DB::table('stock_counts')->where('id', strtolower($id))->when($lock, fn ($q) => $q->lockForUpdate())->first();
        if ($row === null) {
            throw new ModelNotFoundException;
        }
        $lines = DB::table('stock_count_lines')->where('count_id', $row->id)->orderBy('sku')->orderBy('id')
            ->when($lock, fn ($q) => $q->lockForUpdate())->get()->map(function ($line): array {
                $line = (array) $line;
                foreach (['system_quantity', 'counted_quantity', 'unit_cost'] as $key) {
                    $line[$key] = $line[$key] === null ? null : (string) BigDecimal::of($line[$key])->toScale(6);
                }
                $line['difference'] = $line['counted_quantity'] === null ? null
                    : (string) BigDecimal::of($line['counted_quantity'])->minus($line['system_quantity'])->toScale(6);

                return $line;
            })->all();

        return (array) $row + ['lines' => $lines];
    }

    public function create(array $header): void
    {
        DB::table('stock_counts')->insert($header + ['created_at' => now(), 'updated_at' => now()]);
    }

    public function update(string $id, array $changes): void
    {
        if (DB::table('stock_counts')->where('id', $id)->update($changes + ['updated_at' => now()]) !== 1) {
            throw new RuntimeException('Sesi opname tidak tersimpan.');
        }
    }

    public function addLine(array $line): void
    {
        DB::table('stock_count_lines')->insert($line);
    }

    public function updateLine(string $id, array $changes): void
    {
        DB::table('stock_count_lines')->where('id', $id)->update($changes);
    }

    public function balances(string $warehouseId): array
    {
        return DB::table('stock_balances')->where('warehouse_id', $warehouseId)->orderBy('item_id')
            ->lockForUpdate()->get(['item_id', 'quantity'])->map(fn ($row) => (array) $row)->all();
    }

    public function paginate(array $filters, int $page): array
    {
        $records = DB::table('stock_counts')->when($filters['warehouse_id'] ?? '', fn ($q, $id) => $q->where('warehouse_id', $id))
            ->when($filters['status'] ?? '', fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? '', fn ($q, $search) => $q->where(function ($q) use ($search): void {
                $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
                $q->whereRaw("number LIKE ? ESCAPE '!'", [$like])->orWhereRaw("warehouse_name LIKE ? ESCAPE '!'", [$like]);
            }))->orderByDesc('created_at')->orderByDesc('id')->paginate($this->pagination->perPage(), ['id', 'number', 'warehouse_id', 'warehouse_name', 'status', 'version', 'created_at', 'posted_at'], 'page', $page);

        return ['data' => $records->items(), 'meta' => ['current_page' => $records->currentPage(), 'last_page' => $records->lastPage(),
            'per_page' => $records->perPage(), 'total' => $records->total(), 'from' => $records->firstItem(), 'to' => $records->lastItem()]];
    }

    public function audit(string $id, string $event, int $actorId): void
    {
        $record = $this->find($id);
        $log = activity('inventory.stock')->event('count.'.$event)->causedBy($actorId)
            ->withProperties(['count_id' => $id, 'number' => $record['number'], 'warehouse_id' => $record['warehouse_id'],
                'status' => $record['status'], 'version' => $record['version'], 'reason' => $record['reason']])->log('Stock opname '.$event);
        if ($log === null || ! $log->exists) {
            throw new RuntimeException('Audit opname tidak tersimpan.');
        }
    }
}
