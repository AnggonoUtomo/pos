<?php

namespace App\Modules\Finance\Payments\Application\DTOs;

final readonly class PurchaseRefundReceiptResult
{
    public function __construct(public string $id, public string $receivedAt, public array $snapshot) {}
}
