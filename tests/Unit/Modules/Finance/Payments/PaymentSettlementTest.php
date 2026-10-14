<?php

namespace Tests\Unit\Modules\Finance\Payments;

use App\Modules\Finance\Payments\Domain\ValueObjects\PaymentSettlement;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PaymentSettlementTest extends TestCase
{
    public function test_transfer_and_cash_change_produce_the_exact_net_receipts(): void
    {
        $settlement = PaymentSettlement::calculate('100000', [
            ['method_id' => 'transfer', 'type' => 'transfer', 'amount' => '60000'],
            ['method_id' => 'cash', 'type' => 'cash', 'amount' => '50000'],
        ]);
        $this->assertSame('100000.00', $settlement->total);
        $this->assertSame('110000.00', $settlement->tendered);
        $this->assertSame('10000.00', $settlement->change);
        $this->assertSame([
            ['method_id' => 'transfer', 'type' => 'transfer', 'tendered_amount' => '60000.00', 'applied_amount' => '60000.00', 'change_amount' => '0.00'],
            ['method_id' => 'cash', 'type' => 'cash', 'tendered_amount' => '50000.00', 'applied_amount' => '40000.00', 'change_amount' => '10000.00'],
        ], $settlement->payments);
    }

    public function test_cash_position_does_not_change_settlement_or_method_order(): void
    {
        $settlement = PaymentSettlement::calculate('100', [
            ['method_id' => 'cash', 'type' => 'cash', 'amount' => '50'],
            ['method_id' => 'debit', 'type' => 'debit', 'amount' => '25'],
            ['method_id' => 'bank', 'type' => 'transfer', 'amount' => '35'],
        ]);
        $this->assertSame(['cash', 'debit', 'bank'], array_column($settlement->payments, 'method_id'));
        $this->assertSame(['40.00', '25.00', '35.00'], array_column($settlement->payments, 'applied_amount'));
        $this->assertSame('10.00', $settlement->change);
    }

    public function test_exact_noncash_and_cash_only_payments_are_supported(): void
    {
        $result = PaymentSettlement::calculate('0.30', [
            ['method_id' => 'bank', 'type' => 'transfer', 'amount' => '0.1'],
            ['method_id' => 'debit', 'type' => 'debit', 'amount' => '0.20'],
        ]);
        $this->assertSame('0.30', $result->tendered);
        $this->assertSame('0.00', $result->change);
        $this->assertSame(['0.10', '0.20'], array_column($result->payments, 'applied_amount'));
        $cash = PaymentSettlement::calculate('1.01', [['method_id' => 'cash', 'type' => 'cash', 'amount' => '2']]);
        $this->assertSame('0.99', $cash->change);
        $this->assertSame('1.01', $cash->payments[0]['applied_amount']);
    }

    public function test_maximum_money_and_ten_distinct_methods_do_not_use_float(): void
    {
        $max = '9999999999999999999999999999.99';
        $settlement = PaymentSettlement::calculate($max, [['method_id' => 'bank', 'type' => 'transfer', 'amount' => $max]]);
        $this->assertSame($max, $settlement->payments[0]['applied_amount']);
        $payments = array_map(fn ($id) => ['method_id' => 'bank-'.$id, 'type' => 'transfer', 'amount' => '0.01'], range(1, 10));
        $this->assertCount(10, PaymentSettlement::calculate('0.10', $payments)->payments);
    }

    public static function invalidPayments(): array
    {
        $cash = ['method_id' => 'cash', 'type' => 'cash', 'amount' => '100'];
        $bank = ['method_id' => 'bank', 'type' => 'transfer', 'amount' => '100'];

        return [
            'empty' => [[]],
            'not a list' => [['row' => $cash]],
            'two cash methods' => [[$cash, [...$cash, 'method_id' => 'cash-2']]],
            'duplicate normalized method' => [[$bank, [...$bank, 'method_id' => ' BANK ']]],
            'underpaid bank' => [[array_replace($bank, ['amount' => '99.99'])]],
            'overpaid bank' => [[array_replace($bank, ['amount' => '100.01'])]],
            'cash cannot repair overpaid bank' => [[array_replace($bank, ['amount' => '100.01']), $cash]],
            'cash with no contribution' => [[$bank, $cash]],
            'underpaid mixed' => [[array_replace($bank, ['amount' => '50']), array_replace($cash, ['amount' => '49.99'])]],
            'unknown type' => [[array_replace($bank, ['type' => 'qris'])]],
            'missing method' => [[['type' => 'cash', 'amount' => '100']]],
            'blank method' => [[array_replace($cash, ['method_id' => ' '])]],
            'integer method' => [[array_replace($cash, ['method_id' => 1])]],
            'float amount' => [[array_replace($cash, ['amount' => 100.0])]],
            'integer amount' => [[array_replace($cash, ['amount' => 100])]],
            'zero' => [[array_replace($cash, ['amount' => '0'])]],
            'negative' => [[array_replace($cash, ['amount' => '-100'])]],
            'exponent' => [[array_replace($cash, ['amount' => '1e2'])]],
            'localized input' => [[array_replace($cash, ['amount' => '100,00'])]],
            'too precise' => [[array_replace($cash, ['amount' => '100.001'])]],
            'individual overflow' => [[array_replace($cash, ['amount' => '10000000000000000000000000000'])]],
            'aggregate overflow' => [[array_replace($bank, ['amount' => '0.01']), array_replace($cash, ['amount' => '9999999999999999999999999999.99'])]],
            'non array row' => [[null]],
            'too many' => [array_map(fn ($id) => [...$bank, 'method_id' => 'bank-'.$id], range(1, 11))],
        ];
    }

    #[DataProvider('invalidPayments')]
    public function test_invalid_or_unpaid_tenders_are_rejected(array $payments): void
    {
        $this->expectException(InvalidArgumentException::class);
        PaymentSettlement::calculate('100', $payments);
    }

    public static function invalidTotals(): array
    {
        return [['0'], ['-1'], ['1.001'], ['1,00'], ['1e2'], ['10000000000000000000000000000']];
    }

    #[DataProvider('invalidTotals')]
    public function test_total_must_be_a_positive_bounded_exact_decimal(string $total): void
    {
        $this->expectException(InvalidArgumentException::class);
        PaymentSettlement::calculate($total, [['method_id' => 'cash', 'type' => 'cash', 'amount' => '100']]);
    }
}
