<?php

namespace App\Modules\Inventory\Warehouses\Application\DTOs;

final readonly class CreateWarehouseData
{
    public function __construct(
        public string $code,
        public string $name,
        public bool $isActive = true,
    ) {}
}
