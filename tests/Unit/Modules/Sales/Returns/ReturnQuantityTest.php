<?php

namespace Tests\Unit\Modules\Sales\Returns;

use App\Modules\Sales\Returns\Domain\ValueObjects\ReturnQuantity;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReturnQuantityTest extends TestCase
{
    public function test_partial_and_full_quantities_preserve_exact_base_units(): void
    {
        $quantity = ReturnQuantity::from('0.125', '2.000000', '305.000000');
        $this->assertSame('0.125000', $quantity->quantity);
        $this->assertSame('38.125000', $quantity->baseQuantity);
        $this->assertSame('610.000000', ReturnQuantity::from('2', '2', '305')->baseQuantity);
    }

    #[DataProvider('invalidQuantities')]
    public function test_invalid_or_inexact_quantity_is_rejected(string $quantity, string $sold, string $ratio): void
    {
        $this->expectException(InvalidArgumentException::class);
        ReturnQuantity::from($quantity, $sold, $ratio);
    }

    public static function invalidQuantities(): array
    {
        return [['0', '2', '1'], ['-1', '2', '1'], ['3', '2', '1'], ['1,5', '2', '1'], ['1e0', '2', '1'],
            ['0.0000001', '2', '1'], ['0.000001', '2', '0.000001'], ['99999999999999', '99999999999999', '2']];
    }
}
