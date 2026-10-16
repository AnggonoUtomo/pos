<?php

namespace App\Modules\Purchasing\Suppliers\Application\DTOs;

final readonly class SupplierLookupData
{
    public function __construct(public string $id, public string $code, public string $name) {}
}
