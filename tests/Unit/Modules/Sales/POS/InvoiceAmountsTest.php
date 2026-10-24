<?php

namespace Tests\Unit\Modules\Sales\POS;

use App\Modules\Sales\POS\Domain\ValueObjects\InvoiceAmounts;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InvoiceAmountsTest extends TestCase
{
    public function test_percentage_discount_tax_and_residual_are_exact(): void
    {
        $result = InvoiceAmounts::calculate([['subtotal' => '100000.00'], ['subtotal' => '50000.00']],
            ['discount_type' => 'percent', 'discount_value' => '10', 'tax_rate' => '11']);
        $this->assertSame('15000.00', $result['discount_amount']);
        $this->assertSame('14850.00', $result['tax_amount']);
        $this->assertSame('149850.00', $result['total']);
        $this->assertSame('10000.00', $result['lines'][0]['discount_amount']);
        $tiny = InvoiceAmounts::calculate([['subtotal' => '0.01'], ['subtotal' => '0.01'], ['subtotal' => '0.01']],
            ['discount_type' => 'fixed', 'discount_value' => '0.02', 'tax_rate' => '0']);
        $this->assertSame('0.01', $tiny['total']);
        $this->assertSame(['0.00', '0.01', '0.01'], array_column($tiny['lines'], 'discount_amount'));
    }

    public function test_discount_cannot_make_checkout_free_or_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        InvoiceAmounts::calculate([['subtotal' => '100']], ['discount_type' => 'fixed', 'discount_value' => '100', 'tax_rate' => '0']);
    }

    public function test_tax_and_discount_must_have_valid_bounds_and_precision(): void
    {
        foreach ([['tax_rate' => '101'], ['tax_rate' => '1.001'], ['discount_type' => 'alien'], ['discount_value' => '-1'], ['discount_value' => 'NaN']] as $input) {
            try {
                InvoiceAmounts::normalize($input);
                $this->fail('Pricing tidak valid diterima.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
