<?php

namespace App\Modules\Inventory\Items\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\RoundingNecessaryException;
use InvalidArgumentException;

final readonly class ConversionRatio
{
    private function __construct(private BigDecimal $ratio) {}

    public static function fromString(string $value): self
    {
        $ratio = self::decimal($value);

        if ($ratio->isLessThan('1')) {
            throw new InvalidArgumentException('Rasio minimal 1 karena base unit adalah satuan terkecil.');
        }

        return new self($ratio);
    }

    public function value(): string
    {
        return (string) $this->ratio;
    }

    public function assertBaseUnit(): void
    {
        if (! $this->ratio->isEqualTo('1')) {
            throw new InvalidArgumentException('Rasio base unit wajib 1.');
        }
    }

    public function toBaseQuantity(string $quantity): string
    {
        $result = $this->ratio->multipliedBy(self::decimal($quantity));

        if ($result->isGreaterThan('99999999999999.999999')) {
            throw new InvalidArgumentException('Hasil konversi melebihi decimal(20,6).');
        }

        try {
            return (string) $result->toScale(6);
        } catch (RoundingNecessaryException $exception) {
            throw new InvalidArgumentException('Hasil konversi harus tepat pada enam angka desimal.', previous: $exception);
        }
    }

    private static function decimal(string $value): BigDecimal
    {
        if (! preg_match('/^\d{1,14}(?:\.\d{1,6})?$/D', $value)) {
            throw new InvalidArgumentException('Nilai harus decimal string nonnegatif dengan maksimal enam angka desimal.');
        }

        return BigDecimal::of($value)->toScale(6);
    }
}
