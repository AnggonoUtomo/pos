<?php

namespace App\Modules\Sales\Returns\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

final readonly class RefundInvoiceBasis
{
    /** @param array<string, array{quantity: string, net_amount: string}> $lines */
    private function __construct(public string $total, public array $lines) {}

    public static function fromSnapshot(array $header, array $lines): self
    {
        if (! array_is_list($lines) || $lines === [] || count($lines) > 100) {
            throw new InvalidArgumentException('Baris invoice asal untuk refund tidak valid.');
        }

        $fields = ['subtotal', 'discount_amount', 'tax_amount'];
        $totals = array_fill_keys($fields, BigDecimal::zero()->toScale(2));
        $netTotal = BigDecimal::zero()->toScale(2);
        $basis = [];
        foreach ($lines as $row) {
            if (! is_array($row) || ! is_string($row['id'] ?? null) || trim($row['id']) === '' || isset($basis[$row['id']])) {
                throw new InvalidArgumentException('Identitas baris invoice asal tidak valid atau duplikat.');
            }
            $quantity = self::decimal($row['quantity'] ?? null, 14, 6);
            if (! $quantity->isPositive()) {
                throw new InvalidArgumentException('Kuantitas invoice asal harus positif.');
            }
            $amounts = [];
            foreach ($fields as $field) {
                $amounts[$field] = self::decimal($row[$field] ?? null, 28, 2);
                $totals[$field] = $totals[$field]->plus($amounts[$field]);
            }
            if ($amounts['discount_amount']->isGreaterThan($amounts['subtotal'])) {
                throw new InvalidArgumentException('Diskon baris invoice asal melebihi subtotal.');
            }
            $net = self::decimal((string) $amounts['subtotal']->minus($amounts['discount_amount'])->plus($amounts['tax_amount']), 28, 2);
            $netTotal = $netTotal->plus($net);
            $basis[$row['id']] = ['quantity' => (string) $quantity, 'net_amount' => (string) $net];
        }

        foreach ($fields as $field) {
            if (! $totals[$field]->isEqualTo(self::decimal($header[$field] ?? null, 28, 2))) {
                throw new InvalidArgumentException('Jumlah baris tidak sesuai subtotal, diskon atau pajak invoice asal.');
            }
        }
        $total = self::decimal($header['total'] ?? null, 28, 2);
        $paid = self::decimal($header['paid_total'] ?? null, 28, 2);
        $headerNet = $totals['subtotal']->minus($totals['discount_amount'])->plus($totals['tax_amount']);
        if (! $total->isPositive() || ! $total->isEqualTo($netTotal) || ! $total->isEqualTo($headerNet) || ! $total->isEqualTo($paid)) {
            throw new InvalidArgumentException('Nilai bersih invoice tidak sesuai penerimaan pembayaran asal.');
        }

        return new self((string) $total, $basis);
    }

    private static function decimal(mixed $value, int $digits, int $scale): BigDecimal
    {
        if (! is_string($value) || ! preg_match('/^[0-9]{1,'.$digits.'}(?:\.[0-9]{1,'.$scale.'})?$/D', $value)) {
            throw new InvalidArgumentException('Snapshot refund wajib berisi decimal string dengan presisi yang valid.');
        }

        return BigDecimal::of($value)->toScale($scale);
    }
}
