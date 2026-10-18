<?php

namespace App\Modules\Sales\Returns\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final readonly class RefundAllocation
{
    private function __construct(
        public string $quantity,
        public string $amount,
        public string $remainingQuantity,
        public string $remainingAmount,
    ) {}

    public static function calculate(mixed $quantity, mixed $soldQuantity, mixed $soldAmount, mixed $settledQuantity = '0', mixed $settledAmount = '0'): self
    {
        $take = self::decimal($quantity, 14, 6);
        $sold = self::decimal($soldQuantity, 14, 6);
        $net = self::decimal($soldAmount, 28, 2);
        $settled = self::decimal($settledQuantity, 14, 6);
        $paid = self::decimal($settledAmount, 28, 2);

        if (! $take->isPositive() || ! $sold->isPositive() || $settled->isGreaterThan($sold) || $paid->isGreaterThan($net)
            || ($settled->isZero() && ! $paid->isZero())) {
            throw new InvalidArgumentException('Kuantitas atau jejak penyelesaian refund tidak valid.');
        }

        $remainingQuantity = $sold->minus($settled);
        $remainingAmount = $net->minus($paid);
        if ($take->isGreaterThan($remainingQuantity) || ($remainingAmount->isZero() && $net->isPositive() && $remainingQuantity->isPositive())) {
            throw new InvalidArgumentException('Sisa hak refund tidak mencukupi atau tidak konsisten.');
        }

        // Full settlement takes the exact residual; partial settlement never rounds up.
        $amount = $take->isEqualTo($remainingQuantity)
            ? $remainingAmount
            : $remainingAmount->multipliedBy($take)->dividedBy($remainingQuantity, 2, RoundingMode::DOWN);

        return new self((string) $take, (string) $amount,
            (string) $remainingQuantity->minus($take), (string) $remainingAmount->minus($amount));
    }

    private static function decimal(mixed $value, int $digits, int $scale): BigDecimal
    {
        if (! is_string($value) || ! preg_match('/^[0-9]{1,'.$digits.'}(?:\.[0-9]{1,'.$scale.'})?$/D', $value)) {
            throw new InvalidArgumentException('Nilai refund tidak valid atau melebihi batas presisi.');
        }

        return BigDecimal::of($value)->toScale($scale);
    }
}
