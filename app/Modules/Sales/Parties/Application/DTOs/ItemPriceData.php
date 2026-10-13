<?php

namespace App\Modules\Sales\Parties\Application\DTOs;

final readonly class ItemPriceData
{
    public function __construct(public string $unitId, public string $customerLevelId, public string $amount) {}
}
