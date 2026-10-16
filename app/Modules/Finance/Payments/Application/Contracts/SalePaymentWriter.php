<?php

namespace App\Modules\Finance\Payments\Application\Contracts;

use App\Modules\Finance\Payments\Application\DTOs\SalePaymentRequest;
use App\Modules\Finance\Payments\Application\DTOs\SalePaymentResult;

interface SalePaymentWriter
{
    public function receive(SalePaymentRequest $request): SalePaymentResult;
}
