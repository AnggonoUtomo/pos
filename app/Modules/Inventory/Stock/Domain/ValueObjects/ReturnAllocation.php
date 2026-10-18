<?php

namespace App\Modules\Inventory\Stock\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

final class ReturnAllocation
{
    /** Consumption rows must follow their original layer FIFO order. */
    public static function calculate(string $quantity, string $soldQuantity, string $soldValue, array $consumptions): FifoAllocation
    {
        $q = $v = BigDecimal::zero()->toScale(6);
        $pool = [];
        if (! array_is_list($consumptions) || $consumptions === []) {
            throw new InvalidArgumentException('Alokasi FIFO penjualan asal tidak tersedia.');
        }
        foreach ($consumptions as $row) {
            $originalQ = self::decimal($row['quantity'], 14);
            $originalV = self::decimal($row['total_value'], 24);
            $returnedQ = self::decimal($row['returned_quantity'], 14);
            $returnedV = self::decimal($row['returned_value'], 24);
            if (! $originalQ->isPositive() || $returnedQ->isGreaterThan($originalQ) || $returnedV->isGreaterThan($originalV)
                || ($returnedQ->isZero() && ! $returnedV->isZero())) {
                throw new InvalidArgumentException('Jejak alokasi retur tidak konsisten.');
            }
            $q = $q->plus($originalQ);
            $v = $v->plus($originalV);
            $pool[] = ['id' => $row['id'], 'remaining_quantity' => (string) $originalQ->minus($returnedQ),
                'remaining_value' => (string) $originalV->minus($returnedV)];
        }
        if (! $q->isEqualTo(self::decimal($soldQuantity, 14)) || ! $v->isEqualTo(self::decimal($soldValue, 24))) {
            throw new InvalidArgumentException('Konsumsi FIFO tidak sesuai pengeluaran penjualan asal.');
        }

        return FifoAllocation::calculate($quantity, $pool);
    }

    private static function decimal(string $value, int $digits): BigDecimal
    {
        if (! preg_match('/^\d{1,'.$digits.'}(?:\.\d{1,6})?$/D', $value)) {
            throw new InvalidArgumentException('Nilai alokasi retur tidak valid.');
        }

        return BigDecimal::of($value)->toScale(6);
    }
}
