<?php

namespace App\Modules\Inventory\Stock\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Stock\Application\Contracts\StockReceiptRepository;
use App\Modules\Inventory\Stock\Infrastructure\Persistence\Models\StockReceipt;
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

    public function findSource(string $id, bool $lock = false): ?array
    {
        if ($lock) {
            $this->transaction();
        }
        $row = DB::table('stock_receipts')->where('source_type', 'purchase')->where('source_id', $id)->when($lock, fn ($q) => $q->lockForUpdate())->first();

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

    public function receiveLine(string $receiptId, array $header, array $line, array $after, bool $createBalance): void
    {
        $this->transaction();
        $id = strtolower((string) Str::ulid());
        DB::table('stock_receipt_lines')->insert($line + ['id' => $id, 'receipt_id' => $receiptId]);
        $movement = strtolower((string) Str::ulid());
        $pair = ['warehouse_id' => $header['warehouse_id'], 'item_id' => $line['item_id']];
        $snapshot = ['sku' => $line['sku'], 'item_name' => $line['item_name'], 'base_unit_name' => $line['base_unit_name'],
            'warehouse_code' => $header['warehouse_code'], 'warehouse_name' => $header['warehouse_name']];
        DB::table('stock_movements')->insert($pair + $snapshot + ['id' => $movement, 'receipt_line_id' => $id, 'type' => 'purchase',
            'quantity' => $line['quantity'], 'total_value' => $line['total_value'], 'source_number' => $header['source_number'], 'posted_at' => $header['posted_at']]);
        DB::table('fifo_layers')->insert($pair + ['id' => strtolower((string) Str::ulid()), 'movement_id' => $movement,
            'original_quantity' => $line['quantity'], 'remaining_quantity' => $line['quantity'],
            'original_value' => $line['total_value'], 'remaining_value' => $line['total_value'], 'received_at' => $header['posted_at']]);
        if ($createBalance) {
            DB::table('stock_balances')->insert($pair + $snapshot + $after + ['id' => strtolower((string) Str::ulid()), 'opening_line_id' => null, 'created_at' => now(), 'updated_at' => now()]);
        } elseif (DB::table('stock_balances')->where($pair)->update($after + ['updated_at' => now()]) !== 1) {
            throw new RuntimeException('Saldo penerimaan tidak diperbarui.');
        }
    }

    public function audit(string $id, ?int $actorId): void
    {
        $this->transaction();
        $receipt = StockReceipt::findOrFail($id);
        $logger = activity('inventory.stock')->performedOn($receipt)->event('received')->withProperties([
            'source_type' => 'purchase', 'source_id' => $receipt->source_id, 'number' => $receipt->source_number, 'warehouse_id' => $receipt->warehouse_id]);
        if ($actorId === null) {
            $logger->causedByAnonymous();
        } else {
            $logger->causedBy($actorId);
        }
        $log = $logger->log('Penerimaan stok pembelian');
        if ($log === null || ! $log->exists) {
            throw new RuntimeException('Audit receipt tidak tersimpan.');
        }
    }
}
