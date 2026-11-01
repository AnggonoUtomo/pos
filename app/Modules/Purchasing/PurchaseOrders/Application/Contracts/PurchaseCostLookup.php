<?php

namespace App\Modules\Purchasing\PurchaseOrders\Application\Contracts;

use App\Modules\Purchasing\PurchaseOrders\Application\DTOs\PurchaseCostData;

interface PurchaseCostLookup
{
    public function latestForItem(string $itemId): ?PurchaseCostData;
}
