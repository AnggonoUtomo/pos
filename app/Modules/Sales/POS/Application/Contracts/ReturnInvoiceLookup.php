<?php

namespace App\Modules\Sales\POS\Application\Contracts;

use App\Modules\Sales\POS\Application\DTOs\ReturnInvoiceData;
use App\Modules\Sales\POS\Application\DTOs\ReturnRefundBasisData;

interface ReturnInvoiceLookup
{
    /** Snapshot posted, tanpa HPP, payment, atau detail adapter privat. */
    public function find(string $id): ?ReturnInvoiceData;

    public function refundBasis(string $id): ?ReturnRefundBasisData;

    /** @return list<array{id:string,number:string,customer_name:string}> */
    public function search(string $search): array;
}
