<?php

namespace App\Modules\Finance\Payments\Application\Contracts;

use App\Modules\Finance\Payments\Application\DTOs\PurchasePaymentProofData;

interface PurchasePaymentProofLookup
{
    public function find(string $purchaseId, bool $lock = false): ?PurchasePaymentProofData;
}
