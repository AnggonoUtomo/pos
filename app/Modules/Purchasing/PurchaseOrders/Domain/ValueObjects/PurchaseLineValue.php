<?php

namespace App\Modules\Purchasing\PurchaseOrders\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\RoundingNecessaryException;
use InvalidArgumentException;

final readonly class PurchaseLineValue
{
    private function __construct(public string $quantity, public string $ratio, public string $unitCost, public string $baseQuantity, public string $totalValue) {}

    public static function fromStrings(string $quantity, string $ratio, string $unitCost): self
    {
        $quantity = self::positive($quantity);
        $ratio = self::positive($ratio);
        $cost = self::positive($unitCost);
        if ($ratio->isLessThan('1')) {
            throw new InvalidArgumentException('Rasio satuan minimal 1.');
        }
        try {
            $base = $quantity->multipliedBy($ratio)->toScale(6);
            $total = $quantity->multipliedBy($cost)->toScale(6);
        } catch (RoundingNecessaryException $error) {
            throw new InvalidArgumentException('Kuantitas dasar dan nilai pembelian harus tepat pada enam desimal.', previous: $error);
        }
        if ($base->isGreaterThan('99999999999999.999999') || $total->isGreaterThan('999999999999999999999999.999999')) {
            throw new InvalidArgumentException('Kuantitas atau nilai pembelian melebihi kapasitas.');
        }

        return new self((string) $quantity, (string) $ratio, (string) $cost, (string) $base, (string) $total);
    }

    private static function positive(string $input): BigDecimal
    {
        if (! preg_match('/^\d{1,14}(?:\.\d{1,6})?$/D', $input)) {
            throw new InvalidArgumentException('Kuantitas dan harga beli wajib decimal string maksimal enam desimal.');
        }
        $number = BigDecimal::of($input)->toScale(6);
        if (! $number->isPositive()) {
            throw new InvalidArgumentException('Kuantitas dan harga beli wajib lebih dari nol.');
        }

        return $number;
    }
}
