<?php

namespace App\Modules\Finance\Payments\Application\DTOs;

final readonly class PurchasePaymentResult
{
    public function __construct(public string $id, public string $paidAt, public array $snapshot) {}
}
