<?php

namespace App\Modules\Inventory\Stock\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\RoundingNecessaryException;
use InvalidArgumentException;

final readonly class OpeningValuation
{
    private function __construct(
        public string $quantity,
        public string $ratio,
        public string $unitCost,
        public string $baseQuantity,
        public string $totalValue,
    ) {}

    public static function fromStrings(string $quantity, string $ratio, string $unitCost): self
    {
        $quantity = self::positive($quantity);
        $ratio = self::positive($ratio);
        $unitCost = self::positive($unitCost);
        if ($ratio->isLessThan('1')) {
            throw new InvalidArgumentException('Rasio satuan minimal 1.');
        }
        try {
            $base = $quantity->multipliedBy($ratio)->toScale(6);
            $value = $quantity->multipliedBy($unitCost)->toScale(6);
        } catch (RoundingNecessaryException $exception) {
            throw new InvalidArgumentException('Konversi atau nilai stok harus tepat pada enam desimal.', previous: $exception);
        }
        if ($base->isGreaterThan('99999999999999.999999') || $value->isGreaterThan('999999999999999999999999.999999')) {
            throw new InvalidArgumentException('Kuantitas atau nilai stok melebihi kapasitas penyimpanan.');
        }

        return new self((string) $quantity, (string) $ratio, (string) $unitCost, (string) $base, (string) $value);
    }

    private static function positive(string $value): BigDecimal
    {
        if (! preg_match('/^\d{1,14}(?:\.\d{1,6})?$/D', $value)) {
            throw new InvalidArgumentException('Kuantitas dan harga pokok wajib decimal string dengan maksimal enam desimal.');
        }
        $number = BigDecimal::of($value)->toScale(6);
        if (! $number->isPositive()) {
            throw new InvalidArgumentException('Kuantitas, rasio dan harga pokok wajib lebih dari nol.');
        }

        return $number;
    }
}
