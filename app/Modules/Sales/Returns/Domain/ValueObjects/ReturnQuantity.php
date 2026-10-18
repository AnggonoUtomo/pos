<?php

namespace App\Modules\Sales\Returns\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use InvalidArgumentException;

final readonly class ReturnQuantity
{
    private function __construct(public string $quantity, public string $baseQuantity) {}

    public static function from(string $quantity, string $sold, string $ratio): self
    {
        try {
            if (! preg_match('/^\d{1,14}(?:\.\d{1,6})?$/D', $quantity)) {
                throw new InvalidArgumentException('Kuantitas tidak valid.');
            }
            $value = BigDecimal::of($quantity)->toScale(6);
            $conversion = BigDecimal::of($ratio);
            if ($value->isLessThanOrEqualTo(0) || $value->isGreaterThan($sold) || $conversion->isLessThanOrEqualTo(0)) {
                throw new InvalidArgumentException('Kuantitas harus positif dan tidak melebihi jumlah terjual.');
            }
            $base = $value->multipliedBy($conversion)->toScale(6);
            if ($base->isGreaterThan('99999999999999.999999')) {
                throw new InvalidArgumentException('Kuantitas dasar terlalu besar.');
            }

            return new self((string) $value, (string) $base);
        } catch (MathException $e) {
            throw new InvalidArgumentException('Konversi kuantitas harus tepat hingga enam desimal.', previous: $e);
        }
    }
}
