<?php

namespace App\Modules\Purchasing\PurchaseOrders\Application\DTOs;

final readonly class PurchaseCostData
{
    public function __construct(public string $purchaseId, public string $number, public string $receivedAt,
        public string $baseUnitId, public string $unitName, public string $unitCost, public string $conversionRatio) {}
}
