<?php

namespace App\Modules\Inventory\Units\Application\Contracts;

use App\Modules\Inventory\Units\Application\DTOs\CreateUnitData;

interface UnitRepository
{
    public function create(CreateUnitData $data): string;

    /** @return array{data: list<array<string, mixed>>, meta: array<string, int>} */
    public function paginate(int $page): array;
}
