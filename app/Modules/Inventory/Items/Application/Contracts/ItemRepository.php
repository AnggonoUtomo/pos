<?php

namespace App\Modules\Inventory\Items\Application\Contracts;

use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;

interface ItemRepository
{
    public function create(CreateItemData $data): string;
}
