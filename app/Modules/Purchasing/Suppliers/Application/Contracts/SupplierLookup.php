<?php

namespace App\Modules\Purchasing\Suppliers\Application\Contracts;

use App\Modules\Purchasing\Suppliers\Application\DTOs\SupplierLookupData;

interface SupplierLookup
{
    /** Lock requires the shared transaction; contacts are never published. */
    public function findActive(string $id, bool $lock = false): ?SupplierLookupData;

    /** @return list<SupplierLookupData> At most 1000 active input choices. */
    public function choices(): array;
}
