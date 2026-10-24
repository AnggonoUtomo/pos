<?php

namespace App\Modules\Sales\POS\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final class InvoiceAmounts
{
    public static function normalize(array $input): array
    {
        if (array_diff(array_keys($input), ['discount_type', 'discount_value', 'tax_rate'])) {
            throw new InvalidArgumentException('Field diskon/pajak tidak diizinkan.');
        }
        $type = $input['discount_type'] ?? 'fixed';
        $value = $input['discount_value'] ?? '0';
        $rate = $input['tax_rate'] ?? '0';
        if (! in_array($type, ['fixed', 'percent'], true)) {
            throw new InvalidArgumentException('Jenis diskon tidak valid.');
        }
        foreach ([$value, $rate] as $number) {
            if (! is_string($number) || ! preg_match('/^\d{1,28}(?:\.\d{1,2})?$/D', $number)) {
                throw new InvalidArgumentException('Diskon/pajak harus angka nonnegatif maksimal dua desimal.');
            }
        }
        $value = BigDecimal::of($value)->toScale(2);
        $rate = BigDecimal::of($rate)->toScale(2);
        if ($rate->isGreaterThan('100') || ($type === 'percent' && $value->isGreaterThan('100'))) {
            throw new InvalidArgumentException('Persentase tidak boleh melebihi 100.');
        }

        return ['discount_type' => $type, 'discount_value' => (string) $value, 'tax_rate' => (string) $rate];
    }

    public static function calculate(array $lines, array $input = []): array
    {
        $pricing = self::normalize($input);
        $subtotal = BigDecimal::zero()->toScale(2);
        foreach ($lines as $line) {
            $subtotal = $subtotal->plus($line['subtotal']);
        }
        $discount = $pricing['discount_type'] === 'percent'
            ? $subtotal->multipliedBy($pricing['discount_value'])->dividedBy('100', 2, RoundingMode::HALF_UP)
            : BigDecimal::of($pricing['discount_value']);
        if ($discount->isGreaterThanOrEqualTo($subtotal)) {
            throw new InvalidArgumentException('Diskon harus lebih kecil dari subtotal.');
        }
        $cumulative = $allocated = $tax = BigDecimal::zero()->toScale(2);
        foreach ($lines as &$line) {
            $cumulative = $cumulative->plus($line['subtotal']);
            $next = $discount->multipliedBy($cumulative)->dividedBy($subtotal, 2, RoundingMode::DOWN);
            $lineDiscount = $next->minus($allocated);
            $allocated = $next;
            $net = BigDecimal::of($line['subtotal'])->minus($lineDiscount);
            $lineTax = $net->multipliedBy($pricing['tax_rate'])->dividedBy('100', 2, RoundingMode::HALF_UP);
            $line['discount_amount'] = (string) $lineDiscount;
            $line['tax_amount'] = (string) $lineTax;
            $tax = $tax->plus($lineTax);
        }
        unset($line);
        $total = $subtotal->minus($discount)->plus($tax);
        if ($total->isGreaterThan('9999999999999999999999999999.99')) {
            throw new InvalidArgumentException('Total melebihi kapasitas transaksi.');
        }

        return ['lines' => $lines, 'pricing' => $pricing, 'subtotal' => (string) $subtotal,
            'discount_amount' => (string) $discount, 'tax_amount' => (string) $tax, 'total' => (string) $total];
    }
}
