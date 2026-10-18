<?php

namespace App\Modules\Inventory\Stock\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Stock\Application\Contracts\StockReceiptRepository;
use App\Modules\Inventory\Stock\Infrastructure\Persistence\Models\StockReceipt;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

class EloquentStockReceiptRepository implements StockReceiptRepository
{
    private function transaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Receipt mutation/lock memerlukan transaction caller.');
        }
    }

    public function findSource(string $id, bool $lock = false, string $sourceType = 'purchase'): ?array
    {
        if ($lock) {
            $this->transaction();
        }
        $row = DB::table('stock_receipts')->where('source_type', $sourceType)->where('source_id', $id)->when($lock, fn ($q) => $q->lockForUpdate())->first();

        return $row ? (array) $row : null;
    }

    public function create(array $header): string
    {
        $this->transaction();
        $receipt = StockReceipt::create($header);
        if (! $receipt->exists) {
            throw new RuntimeException('Receipt tidak tersimpan.');
        }

        return $receipt->id;
    }

    public function inventory(string $warehouseId, string $itemId): array
    {
        $this->transaction();
        $balance = DB::table('stock_balances')->where('warehouse_id', $warehouseId)->where('item_id', $itemId)->lockForUpdate()->first();
        $layers = DB::table('fifo_layers')->where('warehouse_id', $warehouseId)->where('item_id', $itemId)->orderBy('received_at')->orderBy('id')->lockForUpdate()->get()->map(fn ($row) => (array) $row)->all();

        return [$balance ? (array) $balance : null, $layers];
    }

    public function receiveLine(string $receiptId, array $header, array $line, array $after, bool $createBalance): string
    {
        $this->transaction();
        $id = strtolower((string) Str::ulid());
        DB::table('stock_receipt_lines')->insert($line + ['id' => $id, 'receipt_id' => $receiptId]);
        $movement = strtolower((string) Str::ulid());
        $pair = ['warehouse_id' => $header['warehouse_id'], 'item_id' => $line['item_id']];
        $snapshot = ['sku' => $line['sku'], 'item_name' => $line['item_name'], 'base_unit_name' => $line['base_unit_name'],
            'warehouse_code' => $header['warehouse_code'], 'warehouse_name' => $header['warehouse_name']];
        DB::table('stock_movements')->insert($pair + $snapshot + ['id' => $movement, 'receipt_line_id' => $id, 'type' => $header['source_type'],
            'quantity' => $line['quantity'], 'total_value' => $line['total_value'], 'source_number' => $header['source_number'], 'posted_at' => $header['posted_at']]);
        DB::table('fifo_layers')->insert($pair + ['id' => strtolower((string) Str::ulid()), 'movement_id' => $movement,
            'original_quantity' => $line['quantity'], 'remaining_quantity' => $line['quantity'],
            'original_value' => $line['total_value'], 'remaining_value' => $line['total_value'], 'received_at' => $header['posted_at']]);
        if ($createBalance) {
            DB::table('stock_balances')->insert($pair + $snapshot + $after + ['id' => strtolower((string) Str::ulid()), 'opening_line_id' => null, 'created_at' => now(), 'updated_at' => now()]);
        } elseif (DB::table('stock_balances')->where($pair)->update($after + ['updated_at' => now()]) !== 1) {
            throw new RuntimeException('Saldo penerimaan tidak diperbarui.');
        }

        return $id;
    }

    public function audit(string $id, ?int $actorId): void
    {
        $this->transaction();
        $receipt = StockReceipt::findOrFail($id);
        $logger = activity('inventory.stock')->performedOn($receipt)->event('received')->withProperties([
            'source_type' => $receipt->source_type, 'source_id' => $receipt->source_id, 'number' => $receipt->source_number, 'warehouse_id' => $receipt->warehouse_id]);
        if ($actorId === null) {
            $logger->causedByAnonymous();
        } else {
            $logger->causedBy($actorId);
        }
        $log = $logger->log($receipt->source_type === 'sale_return' ? 'Penerimaan retur penjualan' : 'Penerimaan stok pembelian');
        if ($log === null || ! $log->exists) {
            throw new RuntimeException('Audit receipt tidak tersimpan.');
        }
    }

    public function saleSource(string $invoiceId): ?array
    {
        $this->transaction();
        $issue = DB::table('stock_issues')->where('source_type', 'sale')->where('source_id', $invoiceId)->lockForUpdate()->first();
        if ($issue === null) {
            return null;
        }

        return (array) $issue + ['lines' => DB::table('stock_issue_lines')->where('issue_id', $issue->id)->orderBy('item_id')->orderBy('source_line_id')
            ->lockForUpdate()->get()->map(fn ($row) => (array) $row)->all()];
    }

    public function consumptions(string $issueLineId): array
    {
        $this->transaction();
        $rows = DB::table('fifo_consumptions as c')->join('stock_movements as m', 'm.id', '=', 'c.movement_id')
            ->join('fifo_layers as l', 'l.id', '=', 'c.fifo_layer_id')->where('m.issue_line_id', $issueLineId)
            ->orderBy('l.received_at')->orderBy('l.id')->orderBy('c.id')->select('c.*')->lockForUpdate()->get();
        $returned = DB::table('stock_return_allocations')->whereIn('fifo_consumption_id', $rows->pluck('id'))->orderBy('id')->lockForUpdate()->get()->groupBy('fifo_consumption_id');

        return $rows->map(function ($row) use ($returned): array {
            $q = $v = BigDecimal::zero()->toScale(6);
            foreach ($returned[$row->id] ?? [] as $trace) {
                $q = $q->plus((string) $trace->quantity);
                $v = $v->plus((string) $trace->total_value);
            }

            return ['id' => $row->id, 'quantity' => (string) BigDecimal::of((string) $row->quantity)->toScale(6),
                'total_value' => (string) BigDecimal::of((string) $row->total_value)->toScale(6),
                'returned_quantity' => (string) $q, 'returned_value' => (string) $v];
        })->all();
    }

    public function recordReturnAllocations(string $receiptLineId, array $allocations): void
    {
        $this->transaction();
        foreach ($allocations as $row) {
            DB::table('stock_return_allocations')->insert(['id' => strtolower((string) Str::ulid()), 'receipt_line_id' => $receiptLineId,
                'fifo_consumption_id' => $row['layer_id'], 'quantity' => $row['quantity'], 'total_value' => $row['value']]);
        }
    }
}
