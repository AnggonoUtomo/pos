<?php

namespace App\Modules\Finance\Payments\Application\Contracts;

use App\Modules\Finance\Payments\Application\DTOs\PurchasePaymentRequest;
use App\Modules\Finance\Payments\Application\DTOs\PurchasePaymentResult;

interface PurchasePaymentWriter
{
    public function pay(PurchasePaymentRequest $request): PurchasePaymentResult;
}
