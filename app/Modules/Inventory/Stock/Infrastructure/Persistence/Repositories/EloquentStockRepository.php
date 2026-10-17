<?php

namespace App\Modules\Inventory\Stock\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use App\Modules\Inventory\Stock\Application\Contracts\StockUsage;
use App\Modules\Inventory\Stock\Domain\ValueObjects\FifoAllocation;
use App\Modules\Inventory\Stock\Infrastructure\Persistence\Models\StockIssue;
use App\Modules\Inventory\Stock\Infrastructure\Persistence\Models\StockOpening;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EloquentStockRepository implements StockRepository, StockUsage
{
    public function createDraft(array $header, array $lines): string
    {
        $id = (string) Str::ulid();
        $opening = StockOpening::create($header + ['id' => $id, 'number' => 'SA-'.$id, 'status' => 'draft']);
        if (! $opening->exists) {
            throw new \RuntimeException('Draft tidak tersimpan.');
        }
        $this->insertLines($id, $lines);
        $this->audit($opening, 'created');

        return $id;
    }

    public function lockDraft(string $id, bool $deleted = false): array
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Lookup lock memerlukan transaction.');
        }
        $opening = ($deleted ? StockOpening::onlyTrashed() : StockOpening::query())->whereKey($id)->lockForUpdate()->firstOrFail();

        return $opening->toArray() + ['lines' => $this->lines($id)];
    }

    public function replaceDraft(string $id, array $header, array $lines): void
    {
        $opening = StockOpening::findOrFail($id);
        if (! $opening->update($header)) {
            throw new \RuntimeException('Draft tidak diperbarui.');
        }
        DB::table('stock_opening_lines')->where('opening_id', $id)->delete();
        $this->insertLines($id, $lines);
        $this->audit($opening, 'updated');
    }

    public function deleteDraft(string $id): void
    {
        $opening = StockOpening::findOrFail($id);
        if (! $opening->delete()) {
            throw new \RuntimeException('Draft tidak dihapus.');
        }
        $this->audit($opening, 'deleted');
    }

    public function restoreDraft(string $id): void
    {
        $opening = StockOpening::onlyTrashed()->findOrFail($id);
        if (! $opening->restore()) {
            throw new \RuntimeException('Draft tidak dipulihkan.');
        }
        $this->audit($opening, 'restored');
    }

    public function hasBalance(string $warehouseId, string $itemId): bool
    {
        return DB::table('stock_balances')->where('warehouse_id', $warehouseId)->where('item_id', $itemId)->lockForUpdate()->first(['id']) !== null;
    }

    public function hasItemHistory(string $itemId): bool
    {
        return DB::table('stock_movements')->where('item_id', $itemId)->lockForUpdate()->first(['id']) !== null;
    }

    public function hasWarehouseHistory(string $warehouseId): bool
    {
        return DB::table('stock_movements')->where('warehouse_id', $warehouseId)->lockForUpdate()->first(['id']) !== null;
    }

    public function postOpening(string $id, array $header, array $lines, ?int $actorId): void
    {
        $now = now();
        foreach ($lines as $line) {
            $lineId = $line['id'];
            unset($line['id']);
            DB::table('stock_opening_lines')->where('id', $lineId)->update($line);
            $pair = ['warehouse_id' => $header['warehouse_id'], 'item_id' => $line['item_id']];
            $amount = ['quantity' => $line['base_quantity'], 'total_value' => $line['total_value']];
            DB::table('stock_balances')->insert($pair + $amount + ['id' => (string) Str::ulid(), 'opening_line_id' => $lineId, 'created_at' => $now, 'updated_at' => $now]);
            $movement = (string) Str::ulid();
            DB::table('stock_movements')->insert($pair + $amount + ['id' => $movement, 'opening_line_id' => $lineId, 'type' => 'opening', 'posted_at' => $now]);
            DB::table('fifo_layers')->insert($pair + ['id' => (string) Str::ulid(), 'movement_id' => $movement,
                'original_quantity' => $line['base_quantity'], 'remaining_quantity' => $line['base_quantity'],
                'original_value' => $line['total_value'], 'remaining_value' => $line['total_value'], 'received_at' => $now]);
        }
        $opening = StockOpening::findOrFail($id);
        if (! $opening->update($header + ['status' => 'posted', 'posted_at' => $now, 'posted_by' => $actorId])) {
            throw new \RuntimeException('Posting tidak tersimpan.');
        }
        $this->audit($opening, 'posted');
    }

    public function paginate(string $tab, int $page, array $filters): array
    {
        if ($tab === 'openings') {
            $query = ! empty($filters['trashed']) ? StockOpening::onlyTrashed() : StockOpening::query();
            $query->when($filters['warehouse_id'] ?? '', fn ($q, $id) => $q->where('warehouse_id', $id))
                ->when(($filters['status'] ?? 'all') !== 'all', fn ($q) => $q->where('status', $filters['status']))
                ->when($filters['search'] ?? '', fn ($q, $search) => $q->where(fn ($row) => $row->where('number', 'like', '%'.$search.'%')
                    ->orWhere('warehouse_name', 'like', '%'.$search.'%')));
            $paginator = $query->orderByDesc('id')->paginate(25, page: $page);
            $ids = $paginator->getCollection()->pluck('id')->all();
            $lines = DB::table('stock_opening_lines')->whereIn('opening_id', $ids)->orderBy('item_id')->get()->groupBy('opening_id');
            $rows = $paginator->getCollection()->map(fn ($record) => $record->toArray() + [
                'lines' => ($lines[$record->id] ?? collect())->map(fn ($line) => $this->normalizeLine((array) $line))->all(),
            ])->all();
        } elseif ($tab === 'movements') {
            $fields = ['sku' => 'line.sku', 'item_name' => 'line.item_name', 'base_unit_name' => 'line.base_unit_name',
                'number' => 'opening.number', 'warehouse_name' => 'opening.warehouse_name', 'warehouse_code' => 'opening.warehouse_code'];
            $expressions = [];
            $query = DB::table('stock_movements as ledger')->leftJoin('stock_opening_lines as line', 'line.id', '=', 'ledger.opening_line_id')
                ->leftJoin('stock_openings as opening', 'opening.id', '=', 'line.opening_id')->select('ledger.*');
            foreach ($fields as $alias => $fallback) {
                $column = $alias === 'number' ? 'source_number' : $alias;
                $expressions[$alias] = 'COALESCE(ledger.'.$column.', '.$fallback.')';
                $query->addSelect(DB::raw($expressions[$alias].' as '.$alias));
            }
            $query->when($filters['warehouse_id'] ?? '', fn ($q, $id) => $q->where('ledger.warehouse_id', $id))
                ->when($filters['item_id'] ?? '', fn ($q, $id) => $q->where('ledger.item_id', $id))
                ->when($filters['search'] ?? '', fn ($q, $search) => $q->where(fn ($row) => $row
                    ->whereRaw($expressions['sku'].' like ?', ['%'.$search.'%'])->orWhereRaw($expressions['item_name'].' like ?', ['%'.$search.'%'])
                    ->orWhereRaw($expressions['number'].' like ?', ['%'.$search.'%'])));
            $paginator = $query->orderBy('ledger.posted_at')->orderBy('ledger.id')->paginate(25, page: $page);
            $rows = $paginator->getCollection()->map(fn ($row) => $this->normalizeLine((array) $row))->all();
        } else {
            $query = DB::table('stock_balances as ledger')->leftJoin('stock_opening_lines as line', 'line.id', '=', 'ledger.opening_line_id')
                ->leftJoin('stock_openings as opening', 'opening.id', '=', 'line.opening_id')
                ->select(['ledger.*', 'opening.number']);
            foreach (['sku', 'item_name', 'base_unit_name', 'warehouse_code', 'warehouse_name'] as $field) {
                $fallback = in_array($field, ['warehouse_code', 'warehouse_name'], true) ? 'opening.'.$field : 'line.'.$field;
                $query->addSelect(DB::raw('COALESCE(ledger.'.$field.', '.$fallback.') as '.$field));
            }
            $query
                ->when($filters['warehouse_id'] ?? '', fn ($q, $id) => $q->where('ledger.warehouse_id', $id))
                ->when($filters['item_id'] ?? '', fn ($q, $id) => $q->where('ledger.item_id', $id))
                ->when($filters['search'] ?? '', fn ($q, $search) => $q->where(fn ($row) => $row->whereRaw('COALESCE(ledger.sku, line.sku) like ?', ['%'.$search.'%'])
                    ->orWhereRaw('COALESCE(ledger.item_name, line.item_name) like ?', ['%'.$search.'%'])->orWhere('opening.number', 'like', '%'.$search.'%')));
            $query->orderByRaw('COALESCE(ledger.sku, line.sku)')->orderBy('ledger.id');
            $paginator = $query->paginate(25, page: $page);
            $rows = $paginator->getCollection()->map(fn ($row) => $this->normalizeLine((array) $row))->all();
        }

        return ['data' => $rows, 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(), 'total' => $paginator->total()]];
    }

    protected function lines(string $id): array
    {
        return DB::table('stock_opening_lines')->where('opening_id', $id)->orderBy('item_id')->get()
            ->map(function ($line): array {
                return $this->normalizeLine((array) $line);
            })->all();
    }

    public function lockIssueBySource(string $sourceId): ?array
    {
        $this->assertTransaction();
        $row = DB::table('stock_issues')->where('source_type', 'sale')->where('source_id', $sourceId)->lockForUpdate()->first();
        if ($row === null) {
            return null;
        }
        $result = (array) $row;
        $result['lines'] = DB::table('stock_issue_lines')->where('issue_id', $row->id)->orderBy('line_number')->lockForUpdate()->get()
            ->map(fn ($line) => $this->normalizeLine((array) $line))->all();

        return $result;
    }

    public function createIssue(array $header): string
    {
        $this->assertTransaction();
        $issue = StockIssue::create($header);
        if (! $issue->exists) {
            throw new \RuntimeException('Dokumen pengeluaran tidak tersimpan.');
        }

        return $issue->id;
    }

    public function lockIssueInventory(string $warehouseId, string $itemId): array
    {
        $this->assertTransaction();
        $balance = DB::table('stock_balances')->where('warehouse_id', $warehouseId)->where('item_id', $itemId)->lockForUpdate()->first();
        $layers = DB::table('fifo_layers')->where('warehouse_id', $warehouseId)->where('item_id', $itemId)
            ->orderBy('received_at')->orderBy('id')->lockForUpdate()->get()->map(fn ($row) => (array) $row)->all();

        return [$balance === null ? null : (array) $balance, $layers];
    }

    public function postIssueLine(string $issueId, array $header, array $line, FifoAllocation $allocation, array $balance): void
    {
        $this->assertTransaction();
        $lineId = (string) Str::ulid();
        DB::table('stock_issue_lines')->insert($line + ['id' => $lineId, 'issue_id' => $issueId, 'quantity' => $allocation->quantity, 'total_value' => $allocation->value]);
        $movementId = (string) Str::ulid();
        DB::table('stock_movements')->insert(['id' => $movementId, 'warehouse_id' => $header['warehouse_id'], 'item_id' => $line['item_id'],
            'issue_line_id' => $lineId, 'type' => 'sale', 'quantity' => (string) BigDecimal::of($allocation->quantity)->negated(),
            'total_value' => (string) BigDecimal::of($allocation->value)->negated(), 'posted_at' => $header['posted_at'],
            'source_number' => $header['source_number'], 'sku' => $line['sku'], 'item_name' => $line['item_name'],
            'base_unit_name' => $line['base_unit_name'], 'warehouse_name' => $header['warehouse_name'], 'warehouse_code' => $header['warehouse_code']]);
        foreach ($allocation->allocations as $row) {
            DB::table('fifo_consumptions')->insert(['id' => (string) Str::ulid(), 'movement_id' => $movementId, 'fifo_layer_id' => $row['layer_id'],
                'quantity' => $row['quantity'], 'total_value' => $row['value']]);
            if (DB::table('fifo_layers')->where('id', $row['layer_id'])->update(['remaining_quantity' => $row['remaining_quantity'], 'remaining_value' => $row['remaining_value']]) !== 1) {
                throw new \RuntimeException('Layer FIFO tidak diperbarui.');
            }
        }
        if (DB::table('stock_balances')->where('warehouse_id', $header['warehouse_id'])->where('item_id', $line['item_id'])->update($balance + ['updated_at' => now()]) !== 1) {
            throw new \RuntimeException('Saldo stok tidak diperbarui.');
        }
    }

    public function auditIssue(string $id, ?int $actorId): void
    {
        $this->assertTransaction();
        $issue = StockIssue::findOrFail($id);
        $logger = activity('inventory.stock')->performedOn($issue)->event('issued')
            ->withProperties(['source_type' => $issue->source_type, 'source_id' => $issue->source_id, 'number' => $issue->source_number, 'warehouse_id' => $issue->warehouse_id]);
        if ($actorId === null) {
            $logger->causedByAnonymous();
        } else {
            $logger->causedBy($actorId);
        }
        $activity = $logger->log('Pengeluaran stok penjualan');
        if ($activity === null || ! $activity->exists) {
            throw new \RuntimeException('Audit pengeluaran stok tidak tersimpan.');
        }
    }

    private function assertTransaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Mutation/locking pengeluaran memerlukan transaction default.');
        }
    }

    private function normalizeLine(array $line): array
    {
        if (isset($line['posted_at'])) {
            $line['posted_at'] = Carbon::parse($line['posted_at'], config('app.timezone'))->utc()->toISOString();
        }
        foreach (['quantity', 'conversion_ratio', 'unit_cost', 'base_quantity', 'total_value'] as $key) {
            if (isset($line[$key])) {
                $line[$key] = (string) BigDecimal::of((string) $line[$key])->toScale(6);
            }
        }

        return $line;
    }

    private function insertLines(string $id, array $lines): void
    {
        foreach ($lines as $line) {
            DB::table('stock_opening_lines')->insert($line + ['id' => (string) Str::ulid(), 'opening_id' => $id]);
        }
    }

    protected function audit(StockOpening $opening, string $event): void
    {
        activity('inventory.stock')->performedOn($opening)->event($event)
            ->withProperties(['number' => $opening->number, 'warehouse_id' => $opening->warehouse_id, 'status' => $opening->status])
            ->log('Stok awal '.$event);
    }
}
