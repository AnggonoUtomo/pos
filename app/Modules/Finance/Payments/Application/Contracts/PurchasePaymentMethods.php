<?php

namespace App\Modules\Finance\Payments\Application\Contracts;

interface PurchasePaymentMethods
{
    public function choices(): array;
}
