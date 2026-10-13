<?php

namespace App\Modules\Sales\Parties\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

final readonly class PriceAmount
{
    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        if (! preg_match('/^\d{1,18}(?:\.\d{1,2})?$/D', $value) || ! BigDecimal::of($value)->isGreaterThan(0)) {
            throw new InvalidArgumentException('Harga wajib positif dengan maksimal dua angka desimal.');
        }

        return new self((string) BigDecimal::of($value)->toScale(2));
    }
}
