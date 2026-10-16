<?php

namespace Tests\Unit\Modules\Purchasing\PurchaseOrders;

use App\Modules\Purchasing\PurchaseOrders\Domain\ValueObjects\PurchaseLineValue;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PurchaseLineValueTest extends TestCase
{
    public function test_value_is_per_selected_unit_and_exact(): void
    {
        $value = PurchaseLineValue::fromStrings('2', '12', '120000');
        $this->assertSame('24.000000', $value->baseQuantity);
        $this->assertSame('240000.000000', $value->totalValue);
        $this->assertSame('12.500000', PurchaseLineValue::fromStrings('0.125', '100', '1000')->baseQuantity);
        $this->assertSame('99999999999999.999999', PurchaseLineValue::fromStrings('99999999999999.999999', '1', '1')->quantity);
    }

    public function test_zero_negative_exponent_comma_overflow_and_rounding_are_rejected(): void
    {
        foreach ([['0', '1', '1'], ['1', '1', '0'], ['-1', '1', '1'], ['1e2', '1', '1'],
            ['1,5', '1', '1'], ['1', '0.5', '1'], ['0.000001', '1.000001', '1'],
            ['0.000001', '1', '0.000001'], ['99999999999999', '2', '1'],
            ['99999999999999', '1', '99999999999999'], ['1.0000001', '1', '1']] as $values) {
            try {
                PurchaseLineValue::fromStrings(...$values);
                $this->fail('Invalid precision diterima.');
            } catch (InvalidArgumentException $error) {
                $this->assertNotEmpty($error->getMessage());
            }
        }
    }
}
