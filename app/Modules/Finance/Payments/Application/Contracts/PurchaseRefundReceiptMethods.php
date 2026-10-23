<?php

namespace App\Modules\Finance\Payments\Application\Contracts;

interface PurchaseRefundReceiptMethods
{
    /** @return list<array{id: string, code: string, name: string, type: string}> */
    public function choices(): array;
}
