<?php

namespace App\Modules\Purchasing\PurchaseOrders\Application\Contracts;

use App\Modules\Purchasing\PurchaseOrders\Application\DTOs\ReturnPurchaseData;
use App\Modules\Purchasing\PurchaseOrders\Application\DTOs\ReturnPurchaseRefundBasisData;

interface ReturnPurchaseLookup
{
    public function findEligible(string $id): ?ReturnPurchaseData;

    public function refundBasis(string $id): ?ReturnPurchaseRefundBasisData;

    /** @return list<ReturnPurchaseData> Search is at most 150 characters; results cap at 25. */
    public function searchEligible(string $search): array;
}
