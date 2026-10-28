<?php

namespace App\Modules\Sales\POS\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

final class ShiftCashCount
{
    public static function canonical(string $value): string
    {
        if (! preg_match('/^\d{1,32}(?:\.\d{1,6})?$/D', $value)) {
            throw new InvalidArgumentException('Hitungan fisik harus nol/positif, maksimal 32 digit dan enam desimal.');
        }

        return (string) BigDecimal::of($value)->toScale(6);
    }
}
