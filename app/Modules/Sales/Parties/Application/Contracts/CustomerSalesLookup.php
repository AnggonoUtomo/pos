<?php

namespace App\Modules\Sales\Parties\Application\Contracts;

use App\Modules\Sales\Parties\Application\DTOs\SaleCustomerData;

interface CustomerSalesLookup
{
    /** @return list<SaleCustomerData> At most 1000 active choices, no contact data. */
    public function choices(): array;

    public function findActive(string $id, bool $lock = false): ?SaleCustomerData;
}
