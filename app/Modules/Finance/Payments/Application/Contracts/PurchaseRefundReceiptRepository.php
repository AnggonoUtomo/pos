<?php

namespace App\Modules\Finance\Payments\Application\Contracts;

interface PurchaseRefundReceiptRepository
{
    public function findSource(string $sourceId, bool $lock = false): ?array;

    public function receivedForPurchase(string $disbursementId): string;

    public function create(array $header, array $lines): string;

    public function audit(string $id, ?int $actorId): void;
}
