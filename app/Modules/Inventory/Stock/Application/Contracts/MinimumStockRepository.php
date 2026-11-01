<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

interface MinimumStockRepository
{
    public function find(string $id, bool $lock = false): array;

    public function pairExists(string $warehouseId, string $itemId): bool;

    public function insert(array $record, ?int $actorId): string;

    public function change(string $id, array $changes, string $event, int $actorId): void;

    public function paginate(array $filters, int $page): array;
}
