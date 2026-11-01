<?php

namespace Tests\Unit\Modules\Sales\Parties;

use App\Modules\Sales\Parties\Domain\ValueObjects\MarkupPrice;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MarkupPriceTest extends TestCase
{
    #[DataProvider('prices')]
    public function test_exact_formula_and_final_half_up(string $cost, string $purchaseRatio, string $saleRatio, string $percent, string $expected): void
    {
        $this->assertSame($expected, MarkupPrice::calculate($cost, $purchaseRatio, $saleRatio, $percent));
    }

    public static function prices(): array
    {
        return [['100000', '1', '1', '20', '120000.00'], ['120000', '12', '1', '20', '12000.00'],
            ['120000', '12', '12', '0', '120000.00'], ['1', '3', '3', '0', '1.00'],
            ['0.005', '1', '1', '0', '0.01'], ['99999999999999.999999', '3', '1', '20', '40000000000000.00'],
            ['1000', '1', '2.5', '12.5', '2812.50']];
    }

    #[DataProvider('invalid')]
    public function test_invalid_or_unrepresentable_price_is_not_silently_clamped(string $cost, string $sourceRatio, string $saleRatio, string $percent): void
    {
        $this->expectException(InvalidArgumentException::class);
        MarkupPrice::calculate($cost, $sourceRatio, $saleRatio, $percent);
    }

    public static function invalid(): array
    {
        return [['0', '1', '1', '20'], ['1', '0', '1', '20'], ['1', '1', '1', '-1'], ['1', '1', '1', '1,5'],
            ['1', '1', '1', '1.001'], ['0.000001', '1000000', '1', '0'], ['99999999999999', '1', '99999999999999', '999999']];
    }
}
