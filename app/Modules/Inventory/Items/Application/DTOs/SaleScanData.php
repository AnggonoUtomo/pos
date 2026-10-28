<?php

namespace App\Modules\Inventory\Items\Application\DTOs;

final readonly class SaleScanData
{
    public function __construct(public SaleItemData $item, public string $unitId) {}
}
