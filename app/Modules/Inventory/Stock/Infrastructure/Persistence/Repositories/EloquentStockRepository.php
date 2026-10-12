<?php

namespace App\Modules\Inventory\Stock\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use App\Modules\Inventory\Stock\Application\Contracts\StockUsage;
use App\Modules\Inventory\Stock\Infrastructure\Persistence\Models\StockOpening;
use Brick\Math\BigDecimal;
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

    protected function lines(string $id): array
    {
        return DB::table('stock_opening_lines')->where('opening_id', $id)->orderBy('item_id')->get()
            ->map(function ($line): array {
                $line = (array) $line;
                foreach (['quantity', 'conversion_ratio', 'unit_cost', 'base_quantity', 'total_value'] as $key) {
                    $line[$key] = (string) BigDecimal::of((string) $line[$key])->toScale(6);
                }

                return $line;
            })->all();
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
