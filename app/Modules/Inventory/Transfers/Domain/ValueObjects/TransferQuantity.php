<?php

namespace App\Modules\Inventory\Transfers\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use InvalidArgumentException;

final readonly class TransferQuantity
{
    private function __construct(public string $quantity, public string $ratio, public string $baseQuantity) {}

    public static function fromStrings(string $quantity, string $ratio): self
    {
        foreach ([$quantity, $ratio] as $input) {
            if (! preg_match('/^[0-9]{1,14}(?:\.[0-9]{1,6})?$/D', $input) || ! BigDecimal::of($input)->isPositive()) {
                throw new InvalidArgumentException('Kuantitas dan rasio wajib positif, maksimal enam desimal.');
            }
        }
        $q = BigDecimal::of($quantity)->toScale(6);
        $r = BigDecimal::of($ratio)->toScale(6);
        if ($r->isLessThan('1')) {
            throw new InvalidArgumentException('Rasio satuan minimal 1.');
        }
        try {
            $base = $q->multipliedBy($r)->toScale(6);
        } catch (MathException $error) {
            throw new InvalidArgumentException('Kuantitas dasar harus tepat pada enam desimal.', previous: $error);
        }
        if ($base->isGreaterThan('99999999999999.999999')) {
            throw new InvalidArgumentException('Kuantitas dasar melebihi kapasitas.');
        }

        return new self((string) $q, (string) $r, (string) $base);
    }
}
