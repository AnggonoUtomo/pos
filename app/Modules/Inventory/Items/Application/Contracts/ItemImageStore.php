<?php

namespace App\Modules\Inventory\Items\Application\Contracts;

use App\Modules\Inventory\Items\Application\DTOs\ItemImageFile;
use App\Modules\Inventory\Items\Application\DTOs\ItemImageUpload;

interface ItemImageStore
{
    public function replace(string $itemId, ItemImageUpload $image, string $mediaId): void;

    public function remove(string $itemId): void;

    public function discardUncommitted(string $mediaId): void;

    public function find(string $itemId, string $mediaId): ?ItemImageFile;
}
