<?php

namespace App\Modules\Inventory\Items\Application\Queries;

use App\Modules\Inventory\Items\Application\Contracts\ItemImageStore;
use App\Modules\Inventory\Items\Application\DTOs\ItemImageFile;

class GetItemImage
{
    public function __construct(private ItemImageStore $images) {}

    public function handle(string $itemId, string $mediaId): ?ItemImageFile
    {
        return $this->images->find($itemId, $mediaId);
    }
}
