<?php

namespace App\Modules\Inventory\Units\Application\Contracts;

use App\Modules\Inventory\Units\Application\DTOs\CreateUnitData;

interface UnitRepository
{
    public function lock(string $id, bool $deleted = false): array;

    public function update(string $id, CreateUnitData $data): void;

    public function delete(string $id): void;

    public function restore(string $id): void;

    public function create(CreateUnitData $data): string;

    /** @return array{data: list<array<string, mixed>>, meta: array<string, int>} */
    public function paginate(int $page, array $filters = []): array;
}
