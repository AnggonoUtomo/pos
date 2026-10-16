<?php

namespace App\Modules\Finance\Payments\Application\DTOs;

final readonly class SalePaymentResult
{
    public function __construct(public string $id, public array $snapshot) {}
}
