<?php

namespace App\Modules\Inventory\Items\Application\Queries;

use App\Modules\Inventory\Items\Application\Contracts\ItemRepository;

class ListItems
{
    public function __construct(private ItemRepository $items) {}

    /** @return array{data: list<array<string, mixed>>, meta: array<string, int>} */
    public function handle(int $page = 1): array
    {
        return $this->items->paginate($page);
    }
}
