<?php

namespace Tests\Unit\Modules\Sales\Returns;

use App\Modules\Sales\Returns\Domain\ValueObjects\RefundAllocation;
use Brick\Math\BigDecimal;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RefundAllocationTest extends TestCase
{
    public function test_partial_refunds_keep_two_decimal_residual_until_final_quantity(): void
    {
        $quantity = $amount = '0';
        $values = [];
        foreach (['0.333333', '0.333333', '0.333334'] as $take) {
            $allocation = RefundAllocation::calculate($take, '1', '100', $quantity, $amount);
            $values[] = $allocation->amount;
            $quantity = (string) BigDecimal::of($quantity)->plus($allocation->quantity);
            $amount = (string) BigDecimal::of($amount)->plus($allocation->amount);
        }

        $this->assertSame(['33.33', '33.33', '33.34'], $values);
        $this->assertSame('0.000000', $allocation->remainingQuantity);
        $this->assertSame('0.00', $allocation->remainingAmount);
        $this->assertSame('100.00', $amount);
    }

    public function test_zero_amount_still_consumes_quantity_without_losing_the_money(): void
    {
        $first = RefundAllocation::calculate('0.000001', '1', '0.01');
        $this->assertSame('0.000001', $first->quantity);
        $this->assertSame('0.00', $first->amount);
        $this->assertSame('0.999999', $first->remainingQuantity);
        $this->assertSame('0.01', $first->remainingAmount);

        $last = RefundAllocation::calculate('0.999999', '1', '0.01', $first->quantity, $first->amount);
        $this->assertSame('0.01', $last->amount);
        $this->assertSame('0.00', $last->remainingAmount);
    }

    public function test_a_fully_discounted_original_line_can_be_settled_without_money(): void
    {
        $allocation = RefundAllocation::calculate('2', '2', '0');
        $this->assertSame('2.000000', $allocation->quantity);
        $this->assertSame('0.00', $allocation->amount);
        $this->assertSame('0.000000', $allocation->remainingQuantity);
    }

    public function test_original_sale_unit_quantity_is_not_replaced_with_base_unit_quantity(): void
    {
        $allocation = RefundAllocation::calculate('0.5', '2', '240000');
        $this->assertSame('60000.00', $allocation->amount);
        $this->assertSame('1.500000', $allocation->remainingQuantity);
    }

    public function test_different_refund_orders_conserve_the_same_source_net_amount(): void
    {
        foreach ([['0.000001', '0.4', '0.599999'], ['0.599999', '0.4', '0.000001']] as $order) {
            $quantity = $amount = '0';
            foreach ($order as $take) {
                $allocation = RefundAllocation::calculate($take, '1', '99.99', $quantity, $amount);
                $quantity = (string) BigDecimal::of($quantity)->plus($allocation->quantity);
                $amount = (string) BigDecimal::of($amount)->plus($allocation->amount);
            }
            $this->assertSame('99.99', $amount);
            $this->assertSame('0.000000', $allocation->remainingQuantity);
            $this->assertSame('0.00', $allocation->remainingAmount);
        }
    }

    public function test_maximum_decimal_values_never_pass_through_float_or_machine_integer(): void
    {
        $max = '9999999999999999999999999999.99';
        $allocation = RefundAllocation::calculate('99999999999999.999999', '99999999999999.999999', $max);
        $this->assertSame($max, $allocation->amount);

        $partial = RefundAllocation::calculate('0.000001', '99999999999999.999999', $max);
        $this->assertSame('100000000.00', $partial->amount);
    }

    public function test_many_small_allocations_conserve_every_cent_for_zero_and_positive_sources(): void
    {
        foreach (['0.00', '0.01', '0.02', '0.99', '100.01'] as $net) {
            $quantity = $amount = '0';
            foreach (array_merge(array_fill(0, 99, '0.000001'), ['0.999901']) as $take) {
                $allocation = RefundAllocation::calculate($take, '1', $net, $quantity, $amount);
                $this->assertTrue(BigDecimal::of($allocation->amount)->isGreaterThanOrEqualTo(0));
                $quantity = (string) BigDecimal::of($quantity)->plus($allocation->quantity);
                $amount = (string) BigDecimal::of($amount)->plus($allocation->amount);
                $this->assertTrue(BigDecimal::of($amount)->isLessThanOrEqualTo($net));
            }
            $this->assertSame($net, $amount);
            $this->assertSame('0.000000', $allocation->remainingQuantity);
            $this->assertSame('0.00', $allocation->remainingAmount);
        }
    }

    #[DataProvider('invalidAllocations')]
    public function test_invalid_or_inconsistent_allocations_are_rejected(array $arguments): void
    {
        $this->expectException(InvalidArgumentException::class);
        RefundAllocation::calculate(...$arguments);
    }

    public static function invalidAllocations(): iterable
    {
        yield 'float quantity' => [[0.5, '1', '100']];
        yield 'integer quantity' => [[1, '1', '100']];
        yield 'float net' => [['1', '1', 100.0]];
        yield 'null settled money' => [['1', '1', '100', '0', null]];
        yield 'zero take' => [['0', '1', '100']];
        yield 'negative take' => [['-1', '1', '100']];
        yield 'comma' => [['0,5', '1', '100']];
        yield 'exponent' => [['1e0', '1', '100']];
        yield 'whitespace' => [[' 1', '1', '100']];
        yield 'quantity precision' => [['0.0000001', '1', '100']];
        yield 'quantity overflow' => [['100000000000000', '1', '100']];
        yield 'money overflow' => [['1', '1', '10000000000000000000000000000']];
        yield 'money precision' => [['1', '1', '1.001']];
        yield 'negative source money' => [['1', '1', '-1']];
        yield 'invalid source quantity' => [['1', '0', '100']];
        yield 'over original quantity' => [['1.000001', '1', '100']];
        yield 'over remaining quantity' => [['0.500001', '1', '100', '0.5', '50']];
        yield 'settled quantity overflow' => [['1', '1', '100', '2', '0']];
        yield 'settled money overflow' => [['0.5', '1', '100', '0.5', '100.01']];
        yield 'money without settled quantity' => [['1', '1', '100', '0', '0.01']];
        yield 'negative settled quantity' => [['1', '1', '100', '-1', '0']];
        yield 'negative settled money' => [['0.5', '1', '100', '0.5', '-1']];
        yield 'already exhausted' => [['0.000001', '1', '100', '1', '100']];
        yield 'residual money without quantity' => [['0.000001', '1', '100', '1', '99.99']];
        yield 'positive money exhausted before quantity' => [['0.5', '1', '100', '0.5', '100']];
    }
}
