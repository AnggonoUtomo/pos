<?php

namespace Tests\Unit\Modules\Sales\POS;

use App\Modules\Sales\POS\Domain\ValueObjects\LineAmounts;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LineAmountsTest extends TestCase
{
    public function test_fractional_quantity_and_large_money_are_exact(): void
    {
        $line = LineAmounts::calculate('1.125', '100', '19000.00');
        $this->assertSame('112.500000', $line->baseQuantity);
        $this->assertSame('21375.00', $line->subtotal);
        $this->assertSame('999999999999999999.99', LineAmounts::calculate('1', '1', '999999999999999999.99')->subtotal);
    }

    public function test_rounding_overflow_and_invalid_quantity_are_rejected(): void
    {
        foreach ([['0.000001', '1.000001', '1000000'], ['0.1', '1', '0.01'],
            ['99999999999999', '1', '999999999999999999.99'], ['2', '99999999999999', '1'], ['-1', '1', '1']] as $input) {
            try {
                LineAmounts::calculate(...$input);
                $this->fail('Rounding/overflow/negative must fail.');
            } catch (InvalidArgumentException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }
}
