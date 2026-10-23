<?php

namespace App\Modules\Purchasing\Returns\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final readonly class RefundAllocation
{
    private function __construct(public string $quantity, public string $amount,
        public string $remainingQuantity, public string $remainingAmount) {}

    public static function calculate(mixed $quantity, mixed $receivedQuantity, mixed $originalAmount,
        mixed $settledQuantity = '0', mixed $settledAmount = '0'): self
    {
        $take = self::decimal($quantity, 14);
        $received = self::decimal($receivedQuantity, 14);
        $original = self::decimal($originalAmount, 24);
        $settled = self::decimal($settledQuantity, 14);
        $paid = self::decimal($settledAmount, 24);
        if (! $take->isPositive() || ! $received->isPositive() || $settled->isGreaterThan($received)
            || $paid->isGreaterThan($original) || ($settled->isZero() && ! $paid->isZero())) {
            throw new InvalidArgumentException('Kuantitas atau jejak penerimaan uang retur tidak valid.');
        }
        $remainingQuantity = $received->minus($settled);
        $remainingAmount = $original->minus($paid);
        if ($take->isGreaterThan($remainingQuantity)
            || ($remainingAmount->isZero() && $original->isPositive() && $remainingQuantity->isPositive())) {
            throw new InvalidArgumentException('Sisa hak penerimaan uang retur tidak mencukupi atau tidak konsisten.');
        }
        // Penghabisan hak mengambil residual; bagian sebelumnya tidak dibulatkan naik.
        $amount = $take->isEqualTo($remainingQuantity) ? $remainingAmount
            : $remainingAmount->multipliedBy($take)->dividedBy($remainingQuantity, 6, RoundingMode::DOWN);

        return new self((string) $take, (string) $amount, (string) $remainingQuantity->minus($take),
            (string) $remainingAmount->minus($amount));
    }

    private static function decimal(mixed $value, int $digits): BigDecimal
    {
        if (! is_string($value) || ! preg_match('/^[0-9]{1,'.$digits.'}(?:\.[0-9]{1,6})?$/D', $value)) {
            throw new InvalidArgumentException('Nilai penerimaan uang retur tidak valid atau melebihi presisi.');
        }

        return BigDecimal::of($value)->toScale(6);
    }
}
