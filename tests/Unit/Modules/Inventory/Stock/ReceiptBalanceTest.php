<?php

namespace Tests\Unit\Modules\Inventory\Stock;

use App\Modules\Inventory\Stock\Domain\ValueObjects\ReceiptBalance;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ReceiptBalanceTest extends TestCase
{
    public function test_zero_value_return_can_be_added_and_subsequent_purchase_keeps_balance_exact(): void
    {
        $balance = ReceiptBalance::add(null, [], '2', '0', allowZeroValue: true);
        $this->assertSame(['quantity' => '2.000000', 'total_value' => '0.000000'], $balance);
        $after = ReceiptBalance::add($balance, [['remaining_quantity' => '2', 'remaining_value' => '0']], '1', '100');
        $this->assertSame(['quantity' => '3.000000', 'total_value' => '100.000000'], $after);
    }

    public function test_normal_purchase_receipt_still_requires_positive_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ReceiptBalance::add(null, [], '1', '0');
    }

    public function test_quantity_zero_with_residual_value_remains_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ReceiptBalance::add(['quantity' => '0', 'total_value' => '1'], [['remaining_quantity' => '0', 'remaining_value' => '1']], '1', '0', true);
    }
}
