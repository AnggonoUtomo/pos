<?php

namespace Tests\Unit\Modules\Purchasing\Returns;

use App\Modules\Purchasing\Returns\Domain\ValueObjects\ReturnQuantity;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReturnQuantityTest extends TestCase
{
    #[DataProvider('validQuantities')]
    public function test_quantities_preserve_exact_base_units(string $quantity, string $received, string $ratio, string $expected, string $base): void
    {
        $result = ReturnQuantity::from($quantity, $received, $ratio);
        $this->assertSame($expected, $result->quantity);
        $this->assertSame($base, $result->baseQuantity);
    }

    public static function validQuantities(): array
    {
        return [
            'perangkat unit' => ['1', '2.000000', '1', '1.000000', '1.000000'],
            'kabel rol ke meter' => ['0.125', '2', '305', '0.125000', '38.125000'],
            'konektor dus ke buah' => ['1.5', '2', '12', '1.500000', '18.000000'],
            'pecahan rasio exact' => ['0.5', '1', '1.000002', '0.500000', '0.500001'],
            'satu mikro' => ['0.000001', '1', '1', '0.000001', '0.000001'],
            'batas kapasitas' => ['99999999999999.999999', '99999999999999.999999', '1', '99999999999999.999999', '99999999999999.999999'],
            'presisi di luar float' => ['90071992547409.999999', '90071992547410', '1', '90071992547409.999999', '90071992547409.999999'],
        ];
    }

    #[DataProvider('invalidQuantities')]
    public function test_invalid_inputs_and_inexact_or_overflow_base_are_rejected(mixed $quantity, mixed $received, mixed $ratio): void
    {
        $this->expectException(InvalidArgumentException::class);
        ReturnQuantity::from($quantity, $received, $ratio);
    }

    public static function invalidQuantities(): array
    {
        $invalid = ['', '0', '-1', '+1', '1,5', '1e0', '1.0000001', '100000000000000', ' 1', "1\n", '1.', '.5', "\u{0661}", 1, 1.0, null, true, [], new \stdClass];
        $cases = [];
        foreach ($invalid as $index => $input) {
            $cases['quantity-'.$index] = [$input, '2', '1'];
            $cases['received-'.$index] = ['1', $input, '1'];
            $cases['ratio-'.$index] = ['1', '2', $input];
        }

        return $cases + [
            'lebih dari diterima' => ['2.000001', '2', '1'],
            'rasio kurang satu' => ['1', '2', '0.5'],
            'inexact base' => ['0.000001', '1', '1.000001'],
            'overflow base' => ['99999999999999.999999', '99999999999999.999999', '1.000001'],
        ];
    }
}
