<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

interface StockCountRepository
{
    public function active(string $warehouseId): ?array;

    public function find(string $id, bool $lock = false): array;

    public function create(array $header): void;

    public function update(string $id, array $changes): void;

    public function addLine(array $line): void;

    public function updateLine(string $id, array $changes): void;

    public function balances(string $warehouseId): array;

    public function paginate(array $filters, int $page): array;

    public function audit(string $id, string $event, int $actorId): void;
}
