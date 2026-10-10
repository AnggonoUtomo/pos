<?php

namespace App\Modules\Inventory\Items\Application\Contracts;

use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;

interface ItemRepository
{
    public function lock(string $id, bool $deleted = false): array;

    public function update(string $id, CreateItemData $data): void;

    public function delete(string $id): void;

    public function restore(string $id): void;

    public function create(CreateItemData $data): string;

    public function findIdBySku(string $sku): ?string;

    /** @return array{data: list<array<string, mixed>>, meta: array<string, int>} */
    public function paginate(int $page, array $filters = []): array;
}
