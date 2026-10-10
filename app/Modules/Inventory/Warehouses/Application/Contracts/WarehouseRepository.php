<?php

namespace App\Modules\Inventory\Warehouses\Application\Contracts;

use App\Modules\Inventory\Warehouses\Application\DTOs\CreateWarehouseData;

interface WarehouseRepository
{
    public function lock(string $id, bool $deleted = false): array;

    public function update(string $id, CreateWarehouseData $data): void;

    public function delete(string $id): void;

    public function restore(string $id): void;

    public function create(CreateWarehouseData $data): string;

    /** @return array{data: list<array<string, mixed>>, meta: array<string, int>} */
    public function paginate(int $page): array;
}
