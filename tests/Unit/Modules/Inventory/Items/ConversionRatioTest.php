<?php

namespace Tests\Unit\Modules\Inventory\Items;

use App\Modules\Inventory\Items\Domain\ValueObjects\ConversionRatio;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConversionRatioTest extends TestCase
{
    public function test_it_converts_without_float_and_supports_fractional_quantity(): void
    {
        $this->assertSame('12.000000', ConversionRatio::fromString('12')->value());
        $this->assertSame('24.000000', ConversionRatio::fromString('12')->toBaseQuantity('2'));
        $this->assertSame('125.000000', ConversionRatio::fromString('1000')->toBaseQuantity('0.125'));
        $this->assertSame('0.250000', ConversionRatio::fromString('2.5')->toBaseQuantity('0.1'));
        $this->assertSame('0.000000', ConversionRatio::fromString('1')->toBaseQuantity('0'));
    }

    #[DataProvider('invalidRatios')]
    public function test_it_rejects_invalid_or_smaller_than_base_ratios(string $ratio): void
    {
        $this->expectException(InvalidArgumentException::class);
        ConversionRatio::fromString($ratio);
    }

    public static function invalidRatios(): array
    {
        return array_map(fn ($value) => [$value], ['0', '-1', '0.5', 'NaN', '1e3', '', '1.0000001', '100000000000000']);
    }

    #[DataProvider('invalidQuantities')]
    public function test_it_rejects_invalid_or_inexact_results(string $ratio, string $quantity): void
    {
        $this->expectException(InvalidArgumentException::class);
        ConversionRatio::fromString($ratio)->toBaseQuantity($quantity);
    }

    public static function invalidQuantities(): array
    {
        return [['1', '-1'], ['1', 'NaN'], ['1', '0.0000001'], ['99999999999999', '2'], ['1.000001', '0.001']];
    }
}
