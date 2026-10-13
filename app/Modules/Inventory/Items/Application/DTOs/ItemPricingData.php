<?php

namespace App\Modules\Inventory\Items\Application\DTOs;

final readonly class ItemPricingData
{
    /** @param list<string> $saleUnitIds */
    public function __construct(public string $id, public array $saleUnitIds) {}
}
