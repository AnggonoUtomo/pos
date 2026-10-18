<?php

namespace App\Modules\Inventory\Stock\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

final class ReceiptBalance
{
    public static function add(?array $balance, array $layers, string $quantity, string $value, bool $allowZeroValue = false): array
    {
        $addedQuantity = self::decimal($quantity, 14);
        $addedValue = self::decimal($value, 24);
        if (! $addedQuantity->isPositive() || (! $allowZeroValue && ! $addedValue->isPositive())) {
            throw new InvalidArgumentException('Kuantitas dan nilai penerimaan wajib positif.');
        }
        if ($balance === null && $layers !== []) {
            throw new InvalidArgumentException('Layer FIFO tanpa saldo tidak konsisten.');
        }
        $q = $v = BigDecimal::zero()->toScale(6);
        foreach ($layers as $layer) {
            $lq = self::decimal((string) $layer['remaining_quantity'], 14);
            $lv = self::decimal((string) $layer['remaining_value'], 24);
            if ($lq->isZero() && ! $lv->isZero()) {
                throw new InvalidArgumentException('Sisa kuantitas dan nilai layer tidak konsisten.');
            }
            $q = $q->plus($lq);
            $v = $v->plus($lv);
        }
        if ($balance !== null && (! $q->isEqualTo(self::decimal((string) $balance['quantity'], 14)) || ! $v->isEqualTo(self::decimal((string) $balance['total_value'], 24)))) {
            throw new InvalidArgumentException('Saldo dan sisa layer FIFO tidak konsisten.');
        }
        $q = $q->plus($addedQuantity);
        $v = $v->plus($addedValue);
        if ($q->isGreaterThan('99999999999999.999999') || $v->isGreaterThan('999999999999999999999999.999999')) {
            throw new InvalidArgumentException('Saldo penerimaan melebihi kapasitas.');
        }

        return ['quantity' => (string) $q, 'total_value' => (string) $v];
    }

    private static function decimal(string $value, int $digits): BigDecimal
    {
        if (! preg_match('/^\d{1,'.$digits.'}(?:\.\d{1,6})?$/D', $value)) {
            throw new InvalidArgumentException('Nilai stok harus decimal string nonnegatif sesuai kapasitas.');
        }

        return BigDecimal::of($value)->toScale(6);
    }
}
