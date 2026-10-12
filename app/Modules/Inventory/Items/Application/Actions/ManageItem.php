<?php

namespace App\Modules\Inventory\Items\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemRepository;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Stock\Application\Contracts\StockUsage;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ManageItem
{
    public function __construct(private ItemRepository $items, private CreateItem $creation, private StockUsage $stock) {}

    public function update(string $id, CreateItemData $data): void
    {
        $normalized = $this->creation->normalize($data);
        DB::transaction(function () use ($id, $normalized): void {
            // Match create lock ordering: units first, then the item.
            $this->creation->lockUnits($normalized);
            $current = $this->items->lock($id);
            if ($current['base_unit_id'] !== $normalized->baseUnitId) {
                throw new InvalidArgumentException('Satuan dasar tidak dapat diganti setelah barang dibuat.');
            }
            if ($this->stock->hasItemHistory($id)) {
                if (! $normalized->isActive) {
                    throw new InvalidArgumentException('Barang berhistori stok tidak boleh dinonaktifkan.');
                }
                $ratios = [];
                foreach ($normalized->units as $unit) {
                    $ratios[$unit->unitId] = $unit->conversionRatio;
                }
                foreach ($this->items->conversionRatios($id) as $unitId => $ratio) {
                    if (($ratios[$unitId] ?? null) !== $ratio) {
                        throw new InvalidArgumentException('Satuan barang berhistori stok tidak boleh dihapus atau diubah rasionya.');
                    }
                }
            }
            $this->items->update($id, $normalized);
        });
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->items->lock($id);
            if ($this->stock->hasItemHistory($id)) {
                throw new InvalidArgumentException('Barang berhistori stok tidak boleh dihapus.');
            }
            $this->items->delete($id);
        });
    }

    public function restore(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->items->lock($id, deleted: true);
            $this->items->restore($id);
        });
    }
}
