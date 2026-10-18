<?php

namespace Tests\Unit\Modules\Finance\Payments;

use App\Modules\Finance\Payments\Domain\ValueObjects\RefundSettlement;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RefundSettlementTest extends TestCase
{
    public function test_exact_two_decimal_splits_include_maximum_amount_and_ten_payments(): void
    {
        RefundSettlement::assertExact('0.03', ['0.01', '0.02']);
        RefundSettlement::assertExact('10', array_fill(0, 10, '1'));
        RefundSettlement::assertExact('9999999999999999999999999999.99', ['9999999999999999999999999999.98', '0.01']);
        $this->addToAssertionCount(3);
    }

    #[DataProvider('invalidPayments')]
    public function test_invalid_or_non_exact_payments_are_rejected(string $total, array $amounts): void
    {
        $this->expectException(InvalidArgumentException::class);
        RefundSettlement::assertExact($total, $amounts);
    }

    public static function invalidPayments(): iterable
    {
        yield 'zero total' => ['0', ['0']];
        yield 'empty' => ['1', []];
        yield 'not list' => ['1', ['cash' => '1']];
        yield 'too many' => ['11', array_fill(0, 11, '1')];
        yield 'zero split' => ['1', ['1', '0']];
        yield 'negative' => ['1', ['2', '-1']];
        yield 'less than total' => ['1', ['0.99']];
        yield 'more than total' => ['1', ['1.01']];
        yield 'float' => ['1', [1.0]];
        yield 'exponent' => ['1', ['1e0']];
        yield 'third decimal' => ['1.001', ['1.001']];
        yield 'comma' => ['1', ['1,00']];
        yield 'overflow' => ['10000000000000000000000000000', ['10000000000000000000000000000']];
    }
}
