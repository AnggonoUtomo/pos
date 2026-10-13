<?php

namespace App\Modules\Sales\POS\Application\DTOs;

final readonly class CartLineData
{
    public function __construct(public string $itemId, public string $unitId, public string $quantity) {}
}
