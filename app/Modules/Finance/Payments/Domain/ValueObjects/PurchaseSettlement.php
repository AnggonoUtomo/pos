<?php

namespace App\Modules\Finance\Payments\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

final class PurchaseSettlement
{
    public static function assertExact(string $total, array $amounts): void
    {
        $sum = BigDecimal::zero();
        if (! BigDecimal::of($total)->isPositive()) {
            throw new InvalidArgumentException('Total pembelian harus positif.');
        }
        foreach ($amounts as $amount) {
            if (! BigDecimal::of($amount)->isPositive()) {
                throw new InvalidArgumentException('Setiap pembayaran harus positif.');
            }
            $sum = $sum->plus($amount);
        }
        if (! $sum->isEqualTo($total)) {
            throw new InvalidArgumentException('Jumlah pembayaran harus tepat sama dengan total pembelian, tanpa kembalian.');
        }
    }
}
