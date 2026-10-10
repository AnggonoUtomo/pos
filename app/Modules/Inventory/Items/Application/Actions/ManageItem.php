<?php

namespace App\Modules\Inventory\Items\Application\Actions;

use App\Modules\Inventory\Items\Application\Contracts\ItemRepository;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ManageItem
{
    public function __construct(private ItemRepository $items, private CreateItem $creation) {}

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
            $this->items->update($id, $normalized);
        });
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->items->lock($id);
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
