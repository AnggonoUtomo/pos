<?php

namespace App\Modules\Purchasing\Returns\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use InvalidArgumentException;

final readonly class ReturnQuantity
{
    private function __construct(public string $quantity, public string $baseQuantity) {}

    public static function from(mixed $quantity, mixed $received, mixed $ratio): self
    {
        $quantity = self::positiveDecimal($quantity);
        $received = self::positiveDecimal($received);
        $ratio = self::positiveDecimal($ratio);

        if ($ratio->isLessThan(1)) {
            throw new InvalidArgumentException('Rasio satuan minimal 1.');
        }

        if ($quantity->isGreaterThan($received)) {
            throw new InvalidArgumentException('Kuantitas retur melebihi kuantitas yang diterima.');
        }

        try {
            $baseQuantity = $quantity->multipliedBy($ratio)->toScale(6);
        } catch (MathException $error) {
            throw new InvalidArgumentException('Kuantitas dasar harus tepat hingga enam desimal.', previous: $error);
        }

        if ($baseQuantity->isGreaterThan('99999999999999.999999')) {
            throw new InvalidArgumentException('Kuantitas dasar melebihi kapasitas.');
        }

        return new self((string) $quantity, (string) $baseQuantity);
    }

    private static function positiveDecimal(mixed $value): BigDecimal
    {
        if (! is_string($value) || ! preg_match('/^\d{1,14}(?:\.\d{1,6})?$/D', $value)) {
            throw new InvalidArgumentException('Kuantitas wajib berupa decimal string maksimal enam desimal.');
        }

        try {
            $decimal = BigDecimal::of($value)->toScale(6);
        } catch (MathException $error) {
            throw new InvalidArgumentException('Kuantitas tidak valid.', previous: $error);
        }

        if (! $decimal->isPositive()) {
            throw new InvalidArgumentException('Kuantitas wajib lebih dari nol.');
        }

        return $decimal;
    }
}
