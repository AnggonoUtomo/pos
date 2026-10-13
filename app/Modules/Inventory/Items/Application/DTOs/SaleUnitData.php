<?php

namespace App\Modules\Inventory\Items\Application\DTOs;

final readonly class SaleUnitData
{
    public function __construct(public string $id, public string $code, public string $name,
        public string $ratio, public ?string $barcode) {}
}
