<?php

namespace Tests\Unit\Modules\Inventory\Transfers;

use App\Modules\Inventory\Transfers\Domain\ValueObjects\TransferQuantity;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TransferQuantityTest extends TestCase
{
    public function test_conversion_is_exact_and_never_uses_floats(): void
    {
        $value = TransferQuantity::fromStrings('0.125', '100');
        $this->assertSame('0.125000', $value->quantity);
        $this->assertSame('100.000000', $value->ratio);
        $this->assertSame('12.500000', $value->baseQuantity);
        $this->assertSame('99999999999999.999999', TransferQuantity::fromStrings('99999999999999.999999', '1')->baseQuantity);
    }

    public function test_invalid_nonexact_and_overflow_values_are_rejected(): void
    {
        foreach ([['0', '1'], ['-1', '1'], ['1,5', '1'], ['1e3', '1'], ['1.0000001', '1'], ['1', '0.5'],
            ['99999999999999', '2'], ['0.000001', '1.000001'], ['100000000000000', '1']] as [$quantity, $ratio]) {
            try {
                TransferQuantity::fromStrings($quantity, $ratio);
                $this->fail('Quantity/rasio tidak valid harus ditolak.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
