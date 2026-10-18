<?php

namespace App\Modules\Finance\Payments\Application\DTOs;

final readonly class SaleRefundPaymentResult
{
    public function __construct(public string $id, public string $paidAt, public array $snapshot) {}
}
