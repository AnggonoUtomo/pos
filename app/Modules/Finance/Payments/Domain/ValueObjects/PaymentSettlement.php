<?php

namespace App\Modules\Finance\Payments\Domain\ValueObjects;

use App\Modules\Finance\Payments\Domain\Enums\PaymentMethodType;
use Brick\Math\BigDecimal;
use InvalidArgumentException;

final readonly class PaymentSettlement
{
    private const MAX_AMOUNT = '9999999999999999999999999999.99';

    private function __construct(
        public string $total,
        public string $tendered,
        public string $change,
        public array $payments,
    ) {}

    /** @param list<array{method_id: string, type: string, amount: string}> $tenders */
    public static function calculate(string $total, array $tenders): self
    {
        $due = self::money($total);
        if (! array_is_list($tenders) || count($tenders) < 1 || count($tenders) > 10) {
            throw new InvalidArgumentException('Pembayaran harus berisi 1 sampai 10 metode unik.');
        }
        $noncash = $tendered = BigDecimal::zero()->toScale(2);
        $seen = $rows = [];
        $cashIndex = null;
        foreach ($tenders as $index => $row) {
            if (! is_array($row) || ! is_string($row['method_id'] ?? null) || trim($row['method_id']) === ''
                || ! is_string($row['type'] ?? null) || ! is_string($row['amount'] ?? null)) {
                throw new InvalidArgumentException('Baris pembayaran tidak valid.');
            }
            $id = strtolower(trim($row['method_id']));
            $type = PaymentMethodType::tryFrom($row['type']);
            if ($type === null || isset($seen[$id])) {
                throw new InvalidArgumentException('Jenis metode tidak valid atau metode pembayaran berulang.');
            }
            $seen[$id] = true;
            $amount = self::money($row['amount']);
            if ($type === PaymentMethodType::Cash) {
                if ($cashIndex !== null) {
                    throw new InvalidArgumentException('Maksimal satu baris pembayaran tunai.');
                }
                $cashIndex = $index;
            } else {
                $noncash = $noncash->plus($amount);
            }
            $tendered = $tendered->plus($amount);
            if ($tendered->isGreaterThan(self::MAX_AMOUNT)) {
                throw new InvalidArgumentException('Total pembayaran melebihi batas penyimpanan.');
            }
            $rows[] = ['method_id' => $id, 'type' => $type->value, 'tendered_amount' => (string) $amount];
        }
        if ($noncash->isGreaterThan($due)) {
            throw new InvalidArgumentException('Pembayaran non-tunai tidak boleh melebihi tagihan.');
        }
        $remaining = $due->minus($noncash);
        if ($cashIndex !== null && ! $remaining->isPositive()) {
            throw new InvalidArgumentException('Pembayaran tunai harus berkontribusi pada pelunasan tagihan.');
        }
        if ($tendered->isLessThan($due)) {
            throw new InvalidArgumentException('Pembayaran belum melunasi tagihan.');
        }
        $change = $tendered->minus($due);
        foreach ($rows as $index => &$row) {
            $returned = $index === $cashIndex ? $change : BigDecimal::zero()->toScale(2);
            $row['applied_amount'] = (string) BigDecimal::of($row['tendered_amount'])->minus($returned);
            $row['change_amount'] = (string) $returned;
        }
        unset($row);

        return new self((string) $due, (string) $tendered, (string) $change, $rows);
    }

    private static function money(string $value): BigDecimal
    {
        if (! preg_match('/^\d{1,28}(?:\.\d{1,2})?$/D', $value)) {
            throw new InvalidArgumentException('Nominal wajib decimal string, maksimal dua pecahan dan sesuai batas penyimpanan.');
        }
        $amount = BigDecimal::of($value)->toScale(2);
        if (! $amount->isPositive()) {
            throw new InvalidArgumentException('Nominal harus lebih dari nol.');
        }

        return $amount;
    }
}
