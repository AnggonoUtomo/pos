<?php

namespace Tests\Feature\Modules\Sales\Returns;

use App\Models\User;
use App\Modules\Sales\POS\Application\Actions\PostInvoice;
use App\Modules\Sales\POS\Application\Contracts\ReturnInvoiceLookup;
use App\Modules\Sales\POS\Application\DTOs\CartLineData;
use App\Modules\Sales\POS\Application\Queries\CheckCart;
use App\Modules\Sales\Returns\Database\Seeders\ReturnsAccessSeeder;
use App\Modules\Sales\Returns\Domain\ValueObjects\RefundAllocation;
use App\Modules\Sales\Returns\Domain\ValueObjects\RefundInvoiceBasis;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\Fixtures\SalesReturnFixture;
use Tests\TestCase;

class RefundInvoiceBasisTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_sale_reconciles_both_units_without_changing_any_source_ledger(): void
    {
        $invoice = SalesReturnFixture::seed(withBox: true);
        $before = SalesReturnFixture::fingerprint();
        $basis = RefundInvoiceBasis::fromSnapshot($this->header($invoice), $invoice['lines']);
        $this->assertSame($invoice['total'], $basis->total);
        $this->assertCount(2, $basis->lines);

        $sum = BigDecimal::zero()->toScale(2);
        foreach ($invoice['lines'] as $line) {
            $source = $basis->lines[$line['id']];
            $allocation = RefundAllocation::calculate($source['quantity'], $source['quantity'], $source['net_amount']);
            $this->assertSame($line['subtotal'], $allocation->amount);
            $sum = $sum->plus($allocation->amount);
        }
        $this->assertSame($basis->total, (string) $sum);
        $this->assertSame($before, SalesReturnFixture::fingerprint());
    }

    public function test_cash_change_is_not_part_of_the_refundable_net_receipt(): void
    {
        $first = SalesReturnFixture::seed();
        $line = $first['lines'][0];
        $quote = app(CheckCart::class)->handle($first['warehouse_id'], $first['customer_id'], [new CartLineData($line['item_id'], $line['unit_id'], '1')]);
        $tendered = (string) BigDecimal::of($quote['subtotal'])->plus('100000.00');
        $invoice = app(PostInvoice::class)->handle(['idempotency_key' => strtolower((string) Str::ulid()),
            'warehouse_id' => $first['warehouse_id'], 'customer_id' => $first['customer_id'], 'quote_fingerprint' => $quote['fingerprint'],
            'lines' => [['item_id' => $line['item_id'], 'unit_id' => $line['unit_id'], 'quantity' => '1']],
            'payments' => [['method_id' => $first['payments']['payments'][0]['method_id'], 'amount' => $tendered]]], User::factory()->create()->id);

        $this->assertSame('100000.00', $invoice['payments']['change']);
        $basis = RefundInvoiceBasis::fromSnapshot($this->header($invoice), $invoice['lines']);
        $this->assertSame($invoice['payments']['total'], $basis->total);
        $this->assertNotSame($invoice['payments']['tendered'], $basis->total);

        $header = $this->header($invoice);
        $header['paid_total'] = $invoice['payments']['tendered'];
        $this->expectException(InvalidArgumentException::class);
        RefundInvoiceBasis::fromSnapshot($header, $invoice['lines']);
    }

    public function test_existing_public_draft_lookup_does_not_gain_payment_or_cost_fields(): void
    {
        $this->withoutVite();
        $this->seed(ReturnsAccessSeeder::class);
        $invoice = SalesReturnFixture::seed();
        $before = SalesReturnFixture::fingerprint();
        DB::table('items')->where('id', $invoice['lines'][0]['item_id'])->update(['name' => 'Nama master berubah']);
        $public = app(ReturnInvoiceLookup::class)->find($invoice['id'])->toArray();
        $manager = User::factory()->create()->givePermissionTo('sales.returns.manage');
        $response = $this->actingAs($manager)->getJson('/sales/returns/invoices/'.$invoice['id'])->assertOk()->json();
        $this->assertEquals($public, $response);
        foreach (['payments', 'paid_total', 'payment_receipt_id', 'total_cost', 'request_hash', 'actor_id'] as $private) {
            $this->assertArrayNotHasKey($private, $response);
            $this->assertArrayNotHasKey($private, $response['lines'][0]);
        }
        $this->assertSame($invoice['lines'][0]['name'], $response['lines'][0]['name']);
        $this->assertSame($before, SalesReturnFixture::fingerprint());
    }

    private function header(array $invoice): array
    {
        return array_intersect_key($invoice, array_flip(['subtotal', 'discount_amount', 'tax_amount', 'total']))
            + ['paid_total' => $invoice['payments']['total']];
    }
}
