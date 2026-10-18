<?php

namespace App\Modules\Finance\Payments\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

final class RefundSettlement
{
    public static function assertExact(string $total, array $amounts): void
    {
        $total = self::positive($total);
        if (! array_is_list($amounts) || $amounts === [] || count($amounts) > 10) {
            throw new InvalidArgumentException('Refund memerlukan satu sampai sepuluh pembayaran.');
        }
        $sum = BigDecimal::zero()->toScale(2);
        foreach ($amounts as $amount) {
            $sum = $sum->plus(self::positive($amount));
        }
        if (! $sum->isEqualTo($total)) {
            throw new InvalidArgumentException('Jumlah pembayaran harus tepat sama dengan refund, tanpa kembalian.');
        }
    }

    private static function positive(mixed $value): BigDecimal
    {
        if (! is_string($value) || ! preg_match('/^[0-9]{1,28}(?:\.[0-9]{1,2})?$/D', $value) || ! BigDecimal::of($value)->isPositive()) {
            throw new InvalidArgumentException('Pembayaran refund harus positif dengan presisi dua desimal.');
        }

        return BigDecimal::of($value)->toScale(2);
    }
}
