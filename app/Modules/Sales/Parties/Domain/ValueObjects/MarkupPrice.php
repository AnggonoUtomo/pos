<?php

namespace App\Modules\Sales\Parties\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final class MarkupPrice
{
    public static function calculate(string $unitCost, string $purchaseRatio, string $saleRatio, string $percent): string
    {
        foreach ([$unitCost, $purchaseRatio, $saleRatio] as $value) {
            if (! preg_match('/^\d{1,14}(?:\.\d{1,6})?$/D', $value) || ! BigDecimal::of($value)->isPositive()) {
                throw new InvalidArgumentException('Biaya/konversi wajib positif dan maksimal enam desimal.');
            }
        }
        if (! preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D', $percent)) {
            throw new InvalidArgumentException('Markup wajib nonnegatif, maksimal enam digit dan dua desimal.');
        }
        // Keep the source ratio until the final price to avoid double rounding.
        $amount = BigDecimal::of($unitCost)->multipliedBy($saleRatio)->multipliedBy(BigDecimal::of($percent)->plus('100'))
            ->dividedBy(BigDecimal::of($purchaseRatio)->multipliedBy('100'), 2, RoundingMode::HALF_UP);

        return PriceAmount::fromString((string) $amount)->value;
    }
}
