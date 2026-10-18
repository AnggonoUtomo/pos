<?php

namespace App\Modules\Finance\Payments\Application\Contracts;

use App\Modules\Finance\Payments\Application\DTOs\SaleRefundPaymentRequest;
use App\Modules\Finance\Payments\Application\DTOs\SaleRefundPaymentResult;

interface SaleRefundPaymentWriter
{
    public function pay(SaleRefundPaymentRequest $request): SaleRefundPaymentResult;
}
