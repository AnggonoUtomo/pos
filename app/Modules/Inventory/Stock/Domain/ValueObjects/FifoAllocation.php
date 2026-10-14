<?php

namespace App\Modules\Inventory\Stock\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final readonly class FifoAllocation
{
    private function __construct(
        public string $quantity,
        public string $value,
        public array $allocations,
    ) {}

    /**
     * Caller supplies locked layers for one item/warehouse, ordered by received_at then id.
     *
     * @param  list<array{id: string, remaining_quantity: string, remaining_value: string}>  $layers
     */
    public static function calculate(string $quantity, array $layers): self
    {
        $requested = self::decimal($quantity, 14);
        if (! $requested->isPositive() || ! array_is_list($layers)) {
            throw new InvalidArgumentException('Kuantitas pengeluaran harus positif dan layer berupa daftar berurutan.');
        }
        $seen = $normalized = [];
        foreach ($layers as $layer) {
            if (! is_array($layer) || ! is_string($layer['id'] ?? null) || trim($layer['id']) === '' || $layer['id'] !== trim($layer['id'])
                || ! is_string($layer['remaining_quantity'] ?? null) || ! is_string($layer['remaining_value'] ?? null)) {
                throw new InvalidArgumentException('Data layer FIFO tidak valid.');
            }
            $key = strtolower($layer['id']);
            if (isset($seen[$key])) {
                throw new InvalidArgumentException('Layer FIFO tidak boleh berulang.');
            }
            $seen[$key] = true;
            $available = self::decimal($layer['remaining_quantity'], 14);
            $value = self::decimal($layer['remaining_value'], 24);
            if ($available->isZero() !== $value->isZero()) {
                throw new InvalidArgumentException('Sisa kuantitas dan nilai layer FIFO tidak konsisten.');
            }
            $normalized[] = ['id' => $layer['id'], 'quantity' => $available, 'value' => $value];
        }
        $remaining = $requested;
        $total = BigDecimal::zero()->toScale(6);
        $allocations = [];
        foreach ($normalized as $layer) {
            if ($remaining->isZero()) {
                break;
            }
            $available = $layer['quantity'];
            if ($available->isZero()) {
                continue;
            }
            $taken = $remaining->isLessThan($available) ? $remaining : $available;
            // Full drain takes the residual; rounded unit cost is never the source of value.
            $value = $taken->isEqualTo($available) ? $layer['value']
                : $layer['value']->multipliedBy($taken)->dividedBy($available, 6, RoundingMode::Down);
            $total = $total->plus($value);
            if ($total->isGreaterThan('999999999999999999999999.999999')) {
                throw new InvalidArgumentException('Nilai pengeluaran FIFO melebihi batas penyimpanan.');
            }
            $allocations[] = ['layer_id' => $layer['id'], 'quantity' => (string) $taken, 'value' => (string) $value,
                'remaining_quantity' => (string) $available->minus($taken), 'remaining_value' => (string) $layer['value']->minus($value)];
            $remaining = $remaining->minus($taken);
        }
        if ($remaining->isPositive()) {
            throw new InvalidArgumentException('Sisa layer FIFO tidak mencukupi untuk pengeluaran.');
        }

        return new self((string) $requested, (string) $total, $allocations);
    }

    private static function decimal(string $value, int $integerDigits): BigDecimal
    {
        if (! preg_match('/^\d{1,'.$integerDigits.'}(?:\.\d{1,6})?$/D', $value)) {
            throw new InvalidArgumentException('Kuantitas atau nilai FIFO wajib decimal string non-negatif, maksimal enam pecahan dan sesuai batas penyimpanan.');
        }

        return BigDecimal::of($value)->toScale(6);
    }
}
