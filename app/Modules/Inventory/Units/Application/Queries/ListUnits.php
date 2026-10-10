<?php

namespace App\Modules\Inventory\Units\Application\Queries;

use App\Modules\Inventory\Units\Application\Contracts\UnitRepository;

class ListUnits
{
    public function __construct(private UnitRepository $units) {}

    /** @return array{data: list<array<string, mixed>>, meta: array<string, int>} */
    public function handle(int $page = 1): array
    {
        return $this->units->paginate($page);
    }
}
