<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

interface StockReceiptRepository
{
    public function findSource(string $id, bool $lock = false): ?array;

    public function create(array $header): string;

    public function inventory(string $warehouseId, string $itemId): array;

    public function receiveLine(string $receiptId, array $header, array $line, array $after, bool $createBalance): void;

    public function audit(string $id, ?int $actorId): void;
}
