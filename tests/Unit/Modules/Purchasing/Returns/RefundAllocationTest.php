<?php

namespace Tests\Unit\Modules\Purchasing\Returns;

use App\Modules\Purchasing\Returns\Domain\ValueObjects\RefundAllocation;
use App\Modules\Purchasing\Returns\Domain\ValueObjects\RefundPurchaseBasis;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RefundAllocationTest extends TestCase
{
    public function test_partial_rounds_down_and_final_quantity_takes_exact_residual(): void
    {
        $first = RefundAllocation::calculate('1', '3', '1');
        $second = RefundAllocation::calculate('1', '3', '1', '1', $first->amount);
        $last = RefundAllocation::calculate('1', '3', '1', '2', '0.666666');
        $this->assertSame('0.333333', $first->amount);
        $this->assertSame('0.333333', $second->amount);
        $this->assertSame('0.333334', $last->amount);
        $this->assertSame('0.000000', $last->remainingAmount);
        $this->assertSame('0.000000', $last->remainingQuantity);
    }

    public function test_zero_micro_allocation_preserves_the_last_value(): void
    {
        $first = RefundAllocation::calculate('0.000001', '3', '0.000001');
        $last = RefundAllocation::calculate('2.999999', '3', '0.000001', '0.000001', $first->amount);
        $this->assertSame('0.000000', $first->amount);
        $this->assertSame('0.000001', $last->amount);
    }

    public function test_invalid_quantity_value_precision_and_trace_are_rejected(): void
    {
        foreach ([['0', '1', '1'], ['-1', '1', '1'], ['1.0000001', '2', '1'],
            ['2', '1', '1'], ['1', '2', '1', '2', '0'], ['1', '2', '1', '0', '0.1'],
            ['1', '2', '1', '1', '2'], ['1', '2', '1', '1', '1'],
            ['1', '2', '1000000000000000000000000']] as $input) {
            try {
                RefundAllocation::calculate(...$input);
                $this->fail('Alokasi tidak valid harus ditolak.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_basis_requires_exact_paid_header_and_line_sum(): void
    {
        $header = ['total' => '1800000.000001', 'paid_total' => '1800000.000001'];
        $lines = [['id' => 'line-a', 'quantity' => '2', 'total_value' => '1800000.000001']];
        $basis = RefundPurchaseBasis::fromSnapshot($header, $lines);
        $this->assertSame('1800000.000001', $basis->total);
        $this->assertSame(['quantity' => '2.000000', 'amount' => '1800000.000001'], $basis->lines['line-a']);
        foreach ([[$header + ['unused' => true], []],
            [['total' => '1800000.000001', 'paid_total' => '1800000'], $lines],
            [$header, [$lines[0], $lines[0]]],
            [$header, [['id' => 'line-a', 'quantity' => '2', 'total_value' => '1800000']]]] as [$invalidHeader, $invalidLines]) {
            try {
                RefundPurchaseBasis::fromSnapshot($invalidHeader, $invalidLines);
                $this->fail('Basis sumber tidak valid harus ditolak.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
