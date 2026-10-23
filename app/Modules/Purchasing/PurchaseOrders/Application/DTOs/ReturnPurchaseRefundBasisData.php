<?php

namespace App\Modules\Purchasing\PurchaseOrders\Application\DTOs;

final readonly class ReturnPurchaseRefundBasisData
{
    public function __construct(public array $header, public array $lines) {}
}
