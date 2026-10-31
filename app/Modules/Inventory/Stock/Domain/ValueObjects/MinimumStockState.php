<?php

namespace App\Modules\Inventory\Stock\Domain\ValueObjects;

use Brick\Math\BigDecimal;

final class MinimumStockState
{
    public static function evaluate(string $quantity, string $minimum, bool $active): array
    {
        $quantity = BigDecimal::of($quantity)->toScale(6);
        $minimum = BigDecimal::of($minimum)->toScale(6);
        $low = $active && $quantity->isLessThanOrEqualTo($minimum);

        return ['quantity' => (string) $quantity, 'minimum_quantity' => (string) $minimum,
            'is_low' => $low, 'is_empty' => $low && $quantity->isZero(),
            'shortage' => (string) ($minimum->isGreaterThan($quantity) ? $minimum->minus($quantity) : BigDecimal::zero()->toScale(6))];
    }
}
