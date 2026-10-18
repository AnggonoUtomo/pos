<?php

namespace Tests\Unit\Modules\Inventory\Stock;

use App\Modules\Inventory\Stock\Domain\ValueObjects\FifoAllocation;
use Brick\Math\BigDecimal;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FifoAllocationTest extends TestCase
{
    public function test_layers_are_consumed_in_the_supplied_oldest_first_order(): void
    {
        $layers = [
            ['id' => 'OLD', 'remaining_quantity' => '10', 'remaining_value' => '100'],
            ['id' => 'NEXT', 'remaining_quantity' => '5', 'remaining_value' => '75'],
            ['id' => 'NEW', 'remaining_quantity' => '4', 'remaining_value' => '80'],
        ];
        $result = FifoAllocation::calculate('12', $layers);
        $this->assertSame('12.000000', $result->quantity);
        $this->assertSame('130.000000', $result->value);
        $this->assertSame([
            ['layer_id' => 'OLD', 'quantity' => '10.000000', 'value' => '100.000000', 'remaining_quantity' => '0.000000', 'remaining_value' => '0.000000'],
            ['layer_id' => 'NEXT', 'quantity' => '2.000000', 'value' => '30.000000', 'remaining_quantity' => '3.000000', 'remaining_value' => '45.000000'],
        ], $result->allocations);
        $this->assertSame('10', $layers[0]['remaining_quantity']);
    }

    public function test_repeated_partial_sales_preserve_the_last_residual_exactly(): void
    {
        $layer = ['id' => 'LAYER', 'remaining_quantity' => '3', 'remaining_value' => '1'];
        $values = [];
        foreach (range(1, 3) as $step) {
            $result = FifoAllocation::calculate('1', [$layer]);
            $values[] = $result->value;
            $layer = ['id' => 'LAYER', 'remaining_quantity' => $result->allocations[0]['remaining_quantity'], 'remaining_value' => $result->allocations[0]['remaining_value']];
        }
        $this->assertSame(['0.333333', '0.333333', '0.333334'], $values);
        $this->assertSame('0.000000', $layer['remaining_quantity']);
        $this->assertSame('0.000000', $layer['remaining_value']);
        $this->assertSame('1.000000', (string) BigDecimal::sum(...$values));
    }

    public function test_fractional_base_quantity_and_exhausted_layers_are_supported(): void
    {
        $result = FifoAllocation::calculate('2.5', [
            ['id' => 'empty', 'remaining_quantity' => '0', 'remaining_value' => '0'],
            ['id' => 'cable', 'remaining_quantity' => '4.5', 'remaining_value' => '900'],
        ]);
        $this->assertCount(1, $result->allocations);
        $this->assertSame('500.000000', $result->value);
        $this->assertSame('2.000000', $result->allocations[0]['remaining_quantity']);
        $this->assertSame('400.000000', $result->allocations[0]['remaining_value']);
    }

    public function test_sub_micro_partial_cost_stays_in_the_layer_until_drained(): void
    {
        $result = FifoAllocation::calculate('0.000001', [['id' => 'tiny', 'remaining_quantity' => '3.000001', 'remaining_value' => '0.000003']]);
        $this->assertSame('0.000000', $result->value);
        $remaining = $result->allocations[0];
        $this->assertSame('3.000000', $remaining['remaining_quantity']);
        $this->assertSame('0.000003', $remaining['remaining_value']);
        $last = FifoAllocation::calculate('3', [['id' => 'tiny', 'remaining_quantity' => $remaining['remaining_quantity'], 'remaining_value' => $remaining['remaining_value']]]);
        $this->assertSame('0.000003', $last->value);
        $this->assertSame('0.000000', $last->allocations[0]['remaining_value']);
    }

    public function test_maximum_layer_and_value_remain_exact_without_float(): void
    {
        $quantity = '99999999999999.999999';
        $value = '999999999999999999999999.999999';
        $result = FifoAllocation::calculate($quantity, [['id' => 'max', 'remaining_quantity' => $quantity, 'remaining_value' => $value]]);
        $this->assertSame($quantity, $result->quantity);
        $this->assertSame($value, $result->value);
        $this->assertSame('0.000000', $result->allocations[0]['remaining_value']);
    }

    public function test_splitting_a_layer_conserves_quantity_and_value_after_each_sale(): void
    {
        foreach (range(2, 20) as $quantity) {
            $layer = ['id' => 'layer', 'remaining_quantity' => (string) $quantity, 'remaining_value' => '1.000001'];
            $totalQuantity = $totalValue = BigDecimal::zero()->toScale(6);
            foreach (range(1, $quantity) as $step) {
                $result = FifoAllocation::calculate('1', [$layer]);
                $row = $result->allocations[0];
                $totalQuantity = $totalQuantity->plus($result->quantity);
                $totalValue = $totalValue->plus($result->value);
                $this->assertSame((string) BigDecimal::of($quantity)->toScale(6), (string) $totalQuantity->plus($row['remaining_quantity']));
                $this->assertSame('1.000001', (string) $totalValue->plus($row['remaining_value']));
                $this->assertFalse(BigDecimal::of($row['remaining_quantity'])->isNegative());
                $this->assertFalse(BigDecimal::of($row['remaining_value'])->isNegative());
                $layer = ['id' => 'layer', 'remaining_quantity' => $row['remaining_quantity'], 'remaining_value' => $row['remaining_value']];
            }
        }
    }

    public static function invalid(): array
    {
        $layer = ['id' => 'layer', 'remaining_quantity' => '1', 'remaining_value' => '100'];

        return [
            'zero quantity' => ['0', [$layer]],
            'negative quantity' => ['-1', [$layer]],
            'too precise quantity' => ['0.0000001', [$layer]],
            'overflow quantity' => ['100000000000000', [$layer]],
            'exponent quantity' => ['1e0', [$layer]],
            'localized quantity' => ['1,00', [$layer]],
            'no layers' => ['1', []],
            'shortage' => ['1.000001', [$layer]],
            'only exhausted' => ['1', [[...$layer, 'remaining_quantity' => '0', 'remaining_value' => '0']]],
            'layers not a list' => ['1', ['row' => $layer]],
            'duplicate layer' => ['2', [$layer, $layer]],
            'duplicate case folded layer' => ['2', [$layer, [...$layer, 'id' => 'LAYER']]],
            'missing quantity' => ['1', [['id' => 'bad', 'remaining_value' => '1']]],
            'null row' => ['1', [null]],
            'empty identifier' => ['1', [[...$layer, 'id' => ' ']]],
            'float layer quantity' => ['1', [[...$layer, 'remaining_quantity' => 1.0]]],
            'float layer value' => ['1', [[...$layer, 'remaining_value' => 100.0]]],
            'negative value' => ['1', [[...$layer, 'remaining_value' => '-1']]],
            'too precise value' => ['1', [[...$layer, 'remaining_value' => '1.0000001']]],
            'overflow value' => ['1', [[...$layer, 'remaining_value' => '1000000000000000000000000']]],
            'exhausted with value' => ['1', [[...$layer, 'remaining_quantity' => '0']]],
            'aggregate value overflow' => ['2', [
                [...$layer, 'remaining_value' => '999999999999999999999999.999999'],
                [...$layer, 'id' => 'another', 'remaining_value' => '999999999999999999999999.999999'],
            ]],
        ];
    }

    #[DataProvider('invalid')]
    public function test_invalid_layers_shortage_and_overflow_are_rejected(string $quantity, array $layers): void
    {
        $this->expectException(InvalidArgumentException::class);
        FifoAllocation::calculate($quantity, $layers);
    }

    public function test_zero_cost_return_layers_can_be_sold_partial_full_and_mixed(): void
    {
        $zero = ['id' => 'returned', 'remaining_quantity' => '2', 'remaining_value' => '0'];
        $partial = FifoAllocation::calculate('0.25', [$zero]);
        $this->assertSame('0.000000', $partial->value);
        $this->assertSame('1.750000', $partial->allocations[0]['remaining_quantity']);
        $mixed = FifoAllocation::calculate('3', [$zero, ['id' => 'paid', 'remaining_quantity' => '3', 'remaining_value' => '1']]);
        $this->assertSame('0.333333', $mixed->value);
        $this->assertSame('0.000000', $mixed->allocations[0]['remaining_quantity']);
        $this->assertSame('0.666667', $mixed->allocations[1]['remaining_value']);
    }
}
