<?php

namespace Tests\Unit\Modules\Inventory\Stock;

use App\Modules\Inventory\Stock\Domain\ValueObjects\OpeningValuation;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OpeningValuationTest extends TestCase
{
    public function test_cost_is_per_selected_unit_and_base_conversion_is_exact(): void
    {
        $value = OpeningValuation::fromStrings('2', '12', '120000');
        $this->assertSame('24.000000', $value->baseQuantity);
        $this->assertSame('240000.000000', $value->totalValue);
        $this->assertSame('120000.000000', $value->unitCost);
        $value = OpeningValuation::fromStrings('0.125', '8', '2.4');
        $this->assertSame('1.000000', $value->baseQuantity);
        $this->assertSame('0.300000', $value->totalValue);
    }

    public static function invalid(): array
    {
        return [['0', '1', '1'], ['1', '1', '0'], ['-1', '1', '1'],
            ['1e2', '1', '1'], ['1', '0.5', '1'], ['0.000001', '1.000001', '1'],
            ['0.000001', '1', '0.000001'], ['99999999999999', '2', '1'],
            ['1', '1', '1.0000001'], ['99999999999999', '1', '99999999999999']];
    }

    #[DataProvider('invalid')]
    public function test_invalid_or_inexact_values_are_rejected(string $quantity, string $ratio, string $cost): void
    {
        $this->expectException(InvalidArgumentException::class);
        OpeningValuation::fromStrings($quantity, $ratio, $cost);
    }
}
