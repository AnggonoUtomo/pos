<?php

namespace App\Modules\Sales\POS\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use InvalidArgumentException;

final readonly class LineAmounts
{
    private function __construct(public string $quantity, public string $baseQuantity, public string $subtotal) {}

    public static function calculate(string $quantity, string $ratio, string $price): self
    {
        if (! preg_match('/^\d{1,14}(?:\.\d{1,6})?$/D', $quantity) || ! BigDecimal::of($quantity)->isGreaterThan(0)) {
            throw new InvalidArgumentException('Kuantitas wajib positif, maksimal enam desimal.');
        }
        try {
            $qty = BigDecimal::of($quantity)->toScale(6);
            $base = $qty->multipliedBy($ratio)->toScale(6);
            $amount = $qty->multipliedBy($price)->toScale(2);
            if (! $base->isGreaterThan(0) || $base->isGreaterThan('99999999999999.999999')
                || ! $amount->isGreaterThan(0) || $amount->isGreaterThan('9999999999999999999999999999.99')) {
                throw new InvalidArgumentException('Kuantitas atau nilai barang melebihi batas.');
            }

            return new self((string) $qty, (string) $base, (string) $amount);
        } catch (MathException) {
            throw new InvalidArgumentException('Kuantitas atau nilai memerlukan pembulatan; ubah kuantitas.');
        }
    }
}
