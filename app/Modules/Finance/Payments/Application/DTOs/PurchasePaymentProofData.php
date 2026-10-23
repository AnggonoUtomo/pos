<?php

namespace App\Modules\Finance\Payments\Application\DTOs;

final readonly class PurchasePaymentProofData
{
    public function __construct(public string $id, public string $sourceId, public string $sourceNumber,
        public string $total, public string $paidAt, public array $payments) {}

    public function snapshot(): array
    {
        return ['total' => $this->total, 'payments' => $this->payments];
    }
}
