<?php

namespace App\Modules\Finance\Payments\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

final class CashbookAmount
{
    public static function canonical(string $value, bool $opening = false): string
    {
        if (! preg_match('/^-?\d{1,24}(?:\.\d{1,6})?$/D', $value)) {
            throw new InvalidArgumentException('Gunakan nominal maksimal 24 digit dan enam desimal.');
        }
        $amount = BigDecimal::of($value)->toScale(6);
        if (! $opening && ! $amount->isPositive()) {
            throw new InvalidArgumentException('Nominal mutasi wajib lebih dari nol.');
        }

        return (string) $amount;
    }
}
