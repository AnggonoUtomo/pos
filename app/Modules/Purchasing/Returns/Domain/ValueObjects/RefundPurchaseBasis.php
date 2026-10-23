<?php

namespace App\Modules\Purchasing\Returns\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

final readonly class RefundPurchaseBasis
{
    private function __construct(public string $total, public array $lines) {}

    public static function fromSnapshot(array $header, array $lines): self
    {
        $total = self::decimal($header['total'] ?? null, 24);
        $paid = self::decimal($header['paid_total'] ?? null, 24);
        if (! $total->isPositive() || ! $total->isEqualTo($paid) || $lines === [] || count($lines) > 100) {
            throw new InvalidArgumentException('Pembelian asal harus memiliki nilai dan pembayaran lunas yang sesuai.');
        }
        $sum = BigDecimal::zero()->toScale(6);
        $result = [];
        foreach ($lines as $line) {
            $id = $line['id'] ?? null;
            $quantity = self::decimal($line['quantity'] ?? null, 14);
            $amount = self::decimal($line['total_value'] ?? null, 24);
            if (! is_string($id) || $id === '' || isset($result[$id]) || ! $quantity->isPositive() || ! $amount->isPositive()) {
                throw new InvalidArgumentException('Snapshot nilai baris pembelian tidak valid.');
            }
            $result[$id] = ['quantity' => (string) $quantity, 'amount' => (string) $amount];
            $sum = $sum->plus($amount);
        }
        if (! $sum->isEqualTo($total)) {
            throw new InvalidArgumentException('Jumlah nilai baris berbeda dari total pembelian asal.');
        }

        return new self((string) $total, $result);
    }

    private static function decimal(mixed $value, int $digits): BigDecimal
    {
        if (! is_string($value) || ! preg_match('/^[0-9]{1,'.$digits.'}(?:\.[0-9]{1,6})?$/D', $value)) {
            throw new InvalidArgumentException('Snapshot nominal atau kuantitas pembelian tidak valid.');
        }

        return BigDecimal::of($value)->toScale(6);
    }
}
