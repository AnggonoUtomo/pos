<?php

namespace App\Modules\Inventory\Items\Application\DTOs;

final readonly class ItemPricingData
{
    /** @param list<string> $saleUnitIds
     * @param  list<ItemUnitData>  $saleUnits
     */
    public function __construct(public string $id, public array $saleUnitIds, public array $saleUnits = []) {}
}
