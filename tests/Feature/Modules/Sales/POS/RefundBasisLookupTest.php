<?php

namespace Tests\Feature\Modules\Sales\POS;

use App\Modules\Sales\POS\Application\Contracts\ReturnInvoiceLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\Fixtures\ReturnRefundFixture;
use Tests\TestCase;

class RefundBasisLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_refund_summary_is_exact_and_separate_from_the_draft_projection(): void
    {
        $invoice = ReturnRefundFixture::invoice();
        $lookup = app(ReturnInvoiceLookup::class);
        $summary = $lookup->refundBasis(strtoupper($invoice['id']));
        $this->assertSame(['id', 'subtotal', 'discount_amount', 'tax_amount', 'total', 'paid_total'], array_keys($summary->header));
        $this->assertSame('100.00', $summary->header['paid_total']);
        $this->assertSame(['id', 'quantity', 'subtotal', 'discount_amount', 'tax_amount'], array_keys($summary->lines[0]));
        $this->assertArrayNotHasKey('paid_total', $lookup->find($invoice['id'])->toArray());
        DB::table('sales_invoices')->where('id', $invoice['id'])->update(['payments' => json_encode(['total' => 100])]);
        $this->expectException(InvalidArgumentException::class);
        $lookup->refundBasis($invoice['id']);
    }
}
