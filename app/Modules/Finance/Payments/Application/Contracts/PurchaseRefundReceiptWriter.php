<?php

namespace App\Modules\Finance\Payments\Application\Contracts;

use App\Modules\Finance\Payments\Application\DTOs\PurchaseRefundReceiptRequest;
use App\Modules\Finance\Payments\Application\DTOs\PurchaseRefundReceiptResult;

interface PurchaseRefundReceiptWriter
{
    public function receive(PurchaseRefundReceiptRequest $request): PurchaseRefundReceiptResult;
}
