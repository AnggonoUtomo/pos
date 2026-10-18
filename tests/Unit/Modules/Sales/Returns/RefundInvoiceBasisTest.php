<?php

namespace Tests\Unit\Modules\Sales\Returns;

use App\Modules\Sales\Returns\Domain\ValueObjects\RefundInvoiceBasis;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RefundInvoiceBasisTest extends TestCase
{
    public function test_all_lines_reconcile_subtotal_discount_tax_and_net_receipt_total(): void
    {
        $input = self::snapshot();
        $basis = RefundInvoiceBasis::fromSnapshot($input['header'], $input['lines']);
        $this->assertSame('99.00', $basis->total);
        $this->assertSame(['quantity' => '1.000000', 'net_amount' => '77.00'], $basis->lines['camera-box']);
        $this->assertSame(['quantity' => '2.000000', 'net_amount' => '22.00'], $basis->lines['camera-unit']);
    }

    public function test_same_item_in_different_units_keeps_distinct_original_lines(): void
    {
        $input = self::snapshot();
        $input['lines'][0] += ['item_id' => 'camera', 'unit_id' => 'box', 'conversion_ratio' => '4'];
        $input['lines'][1] += ['item_id' => 'camera', 'unit_id' => 'unit', 'conversion_ratio' => '1'];
        $basis = RefundInvoiceBasis::fromSnapshot($input['header'], $input['lines']);
        $this->assertCount(2, $basis->lines);
        $this->assertSame('1.000000', $basis->lines['camera-box']['quantity']);
        $this->assertSame('2.000000', $basis->lines['camera-unit']['quantity']);
    }

    public function test_zero_net_line_is_valid_but_not_a_zero_paid_invoice(): void
    {
        $input = self::snapshot();
        $input['header'] = ['subtotal' => '100', 'discount_amount' => '20', 'tax_amount' => '0', 'total' => '80', 'paid_total' => '80'];
        $input['lines'][0] = ['id' => 'paid', 'quantity' => '1', 'subtotal' => '80', 'discount_amount' => '0', 'tax_amount' => '0'];
        $input['lines'][1] = ['id' => 'free', 'quantity' => '1', 'subtotal' => '20', 'discount_amount' => '20', 'tax_amount' => '0'];
        $basis = RefundInvoiceBasis::fromSnapshot($input['header'], $input['lines']);
        $this->assertSame('0.00', $basis->lines['free']['net_amount']);
    }

    public function test_snapshot_outputs_do_not_include_hpp_tendered_change_or_current_prices(): void
    {
        $input = self::snapshot();
        $input['header'] += ['tendered' => '150', 'change' => '51', 'total_cost' => '75'];
        $input['lines'][0] += ['unit_price' => '999999', 'total_cost' => '55'];
        $basis = RefundInvoiceBasis::fromSnapshot($input['header'], $input['lines']);
        $this->assertSame(['total', 'lines'], array_keys(get_object_vars($basis)));
        $this->assertSame(['quantity', 'net_amount'], array_keys($basis->lines['camera-box']));
        $this->assertSame('77.00', $basis->lines['camera-box']['net_amount']);
    }

    public function test_maximum_net_and_sum_retain_the_last_cent(): void
    {
        $max = '9999999999999999999999999999.99';
        $header = ['subtotal' => $max, 'discount_amount' => '0', 'tax_amount' => '0', 'total' => $max, 'paid_total' => $max];
        $lines = [
            ['id' => 'large', 'quantity' => '99999999999999.999999', 'subtotal' => '9999999999999999999999999999.98', 'discount_amount' => '0', 'tax_amount' => '0'],
            ['id' => 'cent', 'quantity' => '1', 'subtotal' => '0.01', 'discount_amount' => '0', 'tax_amount' => '0'],
        ];
        $basis = RefundInvoiceBasis::fromSnapshot($header, $lines);
        $this->assertSame($max, $basis->total);
        $this->assertSame('99999999999999.999999', $basis->lines['large']['quantity']);
        $header['total'] = '9999999999999999999999999999.98';
        $this->expectException(InvalidArgumentException::class);
        RefundInvoiceBasis::fromSnapshot($header, $lines);
    }

    #[DataProvider('invalidSnapshots')]
    public function test_inconsistent_or_unsupported_original_amounts_are_rejected(array $overrides): void
    {
        $input = array_replace_recursive(self::snapshot(), $overrides);
        $this->expectException(InvalidArgumentException::class);
        RefundInvoiceBasis::fromSnapshot($input['header'], $input['lines']);
    }

    public static function invalidSnapshots(): iterable
    {
        yield 'wrong total' => [['header' => ['total' => '98.99']]];
        yield 'tendered mistaken for paid net' => [['header' => ['paid_total' => '150']]];
        yield 'unallocated header discount' => [['header' => ['discount_amount' => '11', 'total' => '98', 'paid_total' => '98']]];
        yield 'unallocated header tax' => [['header' => ['tax_amount' => '10', 'total' => '100', 'paid_total' => '100']]];
        yield 'different line subtotal' => [['lines' => [0 => ['subtotal' => '80.01']]]];
        yield 'different line discount' => [['lines' => [0 => ['discount_amount' => '9.99']]]];
        yield 'different line tax' => [['lines' => [0 => ['tax_amount' => '7.01']]]];
        yield 'duplicate original line' => [['lines' => [1 => ['id' => 'camera-box']]]];
        yield 'missing line id' => [['lines' => [0 => ['id' => null]]]];
        yield 'empty line id' => [['lines' => [0 => ['id' => '']]]];
        yield 'missing payment net' => [['header' => ['paid_total' => null]]];
        yield 'float money' => [['header' => ['subtotal' => 100.0]]];
        yield 'integer money' => [['lines' => [0 => ['tax_amount' => 7]]]];
        yield 'negative amount' => [['lines' => [0 => ['tax_amount' => '-7']]]];
        yield 'money exponent' => [['header' => ['subtotal' => '1e2']]];
        yield 'money comma' => [['header' => ['total' => '99,00']]];
        yield 'money precision' => [['lines' => [0 => ['tax_amount' => '7.001']]]];
        yield 'money overflow' => [['header' => ['subtotal' => '10000000000000000000000000000']]];
        yield 'net overflow' => [['lines' => [0 => ['subtotal' => '9999999999999999999999999999.99', 'discount_amount' => '0', 'tax_amount' => '0.02']]]];
        yield 'negative net' => [['lines' => [0 => ['discount_amount' => '80.01']]]];
        yield 'zero sold quantity' => [['lines' => [0 => ['quantity' => '0']]]];
        yield 'float quantity' => [['lines' => [0 => ['quantity' => 1.0]]]];
        yield 'quantity overflow' => [['lines' => [0 => ['quantity' => '100000000000000']]]];
        yield 'quantity precision' => [['lines' => [0 => ['quantity' => '0.0000001']]]];
        yield 'malformed line' => [['lines' => [0 => null]]];
    }

    public function test_empty_non_list_or_oversized_sources_are_rejected(): void
    {
        $input = self::snapshot();
        foreach ([[], ['first' => $input['lines'][0]], array_fill(0, 101, $input['lines'][0])] as $lines) {
            try {
                RefundInvoiceBasis::fromSnapshot($input['header'], $lines);
                $this->fail('Invalid source line collection was accepted.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_zero_invoice_is_not_a_supported_paid_sale_source(): void
    {
        $this->expectException(InvalidArgumentException::class);
        RefundInvoiceBasis::fromSnapshot(['subtotal' => '0', 'discount_amount' => '0', 'tax_amount' => '0', 'total' => '0', 'paid_total' => '0'],
            [['id' => 'zero', 'quantity' => '1', 'subtotal' => '0', 'discount_amount' => '0', 'tax_amount' => '0']]);
    }

    private static function snapshot(): array
    {
        return ['header' => ['subtotal' => '100', 'discount_amount' => '10', 'tax_amount' => '9', 'total' => '99', 'paid_total' => '99'],
            'lines' => [
                ['id' => 'camera-box', 'quantity' => '1', 'subtotal' => '80', 'discount_amount' => '10', 'tax_amount' => '7'],
                ['id' => 'camera-unit', 'quantity' => '2', 'subtotal' => '20', 'discount_amount' => '0', 'tax_amount' => '2'],
            ]];
    }
}
