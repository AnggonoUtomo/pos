<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

interface StockReceiptRepository
{
    public function findSource(string $id, bool $lock = false, string $sourceType = 'purchase'): ?array;

    public function create(array $header): string;

    public function inventory(string $warehouseId, string $itemId): array;

    public function receiveLine(string $receiptId, array $header, array $line, array $after, bool $createBalance): string;

    public function saleSource(string $invoiceId): ?array;

    public function consumptions(string $issueLineId): array;

    public function recordReturnAllocations(string $receiptLineId, array $allocations): void;

    public function audit(string $id, ?int $actorId): void;
}
