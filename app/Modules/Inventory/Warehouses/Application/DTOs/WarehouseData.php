<?php

namespace App\Modules\Inventory\Warehouses\Application\DTOs;

final readonly class WarehouseData
{
    public function __construct(public string $id, public string $code, public string $name) {}
}
