<?php

namespace Tests\Unit\Modules\Inventory\Stock;

use App\Modules\Inventory\Stock\Domain\ValueObjects\ReturnAllocation;
use Brick\Math\BigDecimal;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ReturnAllocationTest extends TestCase
{
    public function test_partial_repeated_full_returns_conserve_consumption_residual(): void
    {
        $pool = [['id' => 'old', 'quantity' => '3', 'total_value' => '1', 'returned_quantity' => '0', 'returned_value' => '0']];
        $values = [];
        foreach (range(1, 3) as $step) {
            $result = ReturnAllocation::calculate('1', '3', '1', $pool);
            $values[] = $result->value;
            $pool[0]['returned_quantity'] = (string) BigDecimal::of($pool[0]['returned_quantity'])->plus($result->quantity);
            $pool[0]['returned_value'] = (string) BigDecimal::of($pool[0]['returned_value'])->plus($result->value);
        }
        $this->assertSame(['0.333333', '0.333333', '0.333334'], $values);
        $this->assertSame('1.000000', $pool[0]['returned_value']);
    }

    public function test_multiple_original_consumptions_and_zero_value_are_supported(): void
    {
        $result = ReturnAllocation::calculate('1.5', '3', '10', [
            ['id' => 'first', 'quantity' => '1', 'total_value' => '0', 'returned_quantity' => '0', 'returned_value' => '0'],
            ['id' => 'last', 'quantity' => '2', 'total_value' => '10', 'returned_quantity' => '0', 'returned_value' => '0']]);
        $this->assertSame('2.500000', $result->value);
        $this->assertCount(2, $result->allocations);
    }

    public function test_over_return_or_source_consumption_mismatch_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ReturnAllocation::calculate('1.000001', '2', '10', [['id' => 'one', 'quantity' => '2', 'total_value' => '10', 'returned_quantity' => '1', 'returned_value' => '5']]);
    }

    public function test_inconsistent_consumptions_are_not_valued_silently(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ReturnAllocation::calculate('1', '2', '11', [['id' => 'one', 'quantity' => '2', 'total_value' => '10', 'returned_quantity' => '0', 'returned_value' => '0']]);
    }
}
