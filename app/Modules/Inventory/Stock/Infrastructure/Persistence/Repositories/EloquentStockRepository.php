<?php

namespace App\Modules\Inventory\Stock\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use App\Modules\Inventory\Stock\Infrastructure\Persistence\Models\StockOpening;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EloquentStockRepository implements StockRepository
{
    public function createDraft(array $header, array $lines): string
    {
        $id = (string) Str::ulid();
        $opening = StockOpening::create($header + ['id' => $id, 'number' => 'SA-'.$id, 'status' => 'draft']);
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
        $opening->update($header);
        DB::table('stock_opening_lines')->where('opening_id', $id)->delete();
        $this->insertLines($id, $lines);
        $this->audit($opening, 'updated');
    }

    public function deleteDraft(string $id): void
    {
        $opening = StockOpening::findOrFail($id);
        $opening->delete();
        $this->audit($opening, 'deleted');
    }

    public function restoreDraft(string $id): void
    {
        $opening = StockOpening::onlyTrashed()->findOrFail($id);
        $opening->restore();
        $this->audit($opening, 'restored');
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
