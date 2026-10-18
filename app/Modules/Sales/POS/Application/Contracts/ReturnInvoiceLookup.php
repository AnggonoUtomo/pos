<?php

namespace App\Modules\Sales\POS\Application\Contracts;

use App\Modules\Sales\POS\Application\DTOs\ReturnInvoiceData;

interface ReturnInvoiceLookup
{
    /** Snapshot posted, tanpa HPP, payment, atau detail adapter privat. */
    public function find(string $id): ?ReturnInvoiceData;

    /** @return list<array{id:string,number:string,customer_name:string}> */
    public function search(string $search): array;
}
