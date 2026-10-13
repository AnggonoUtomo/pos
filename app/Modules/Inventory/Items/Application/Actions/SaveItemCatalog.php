<?php

namespace App\Modules\Inventory\Items\Application\Actions;

use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceCatalog;
use Illuminate\Support\Facades\DB;

class SaveItemCatalog
{
    public function __construct(private CreateItem $create, private ManageItem $manage, private ItemPriceCatalog $prices) {}

    public function save(CreateItemData $data, array $prices, ?string $id = null): string
    {
        return DB::transaction(function () use ($data, $prices, $id): string {
            $this->prices->activeLevels(lock: true);
            if ($id === null) {
                $id = $this->create->handle($data);
            } else {
                $this->manage->update($id, $data);
            }
            $this->prices->saveForItem($id, $prices);

            return $id;
        }, 3);
    }
}
