<?php

namespace App\Modules\Purchasing\Suppliers\Application\DTOs;

final readonly class SupplierData
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $address = null,
        public bool $isActive = true,
    ) {}
}
