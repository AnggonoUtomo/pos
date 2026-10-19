<?php

namespace Tests\Feature\Modules\Sales\Returns;

use App\Models\User;
use App\Modules\Sales\Returns\Application\Actions\SettleReturnRefund;
use App\Modules\Sales\Returns\Application\Queries\QuoteReturnRefund;
use App\Modules\Sales\Returns\Database\Seeders\ReturnsAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\ReturnRefundFixture;
use Tests\Fixtures\SalesReturnFixture;
use Tests\TestCase;

class ReturnRefundReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_reads_safe_refund_result_for_recovery_without_refund_permission(): void
    {
        $this->seed(ReturnsAccessSeeder::class);
        $invoice = ReturnRefundFixture::invoice('0.01');
        $zero = ReturnRefundFixture::posted($invoice, '0.000001');
        $positive = ReturnRefundFixture::posted($invoice, '0.999999');
        foreach ([$zero, $positive] as $id) {
            $quote = app(QuoteReturnRefund::class)->handle($id);
            app(SettleReturnRefund::class)->handle($id, ['version' => $quote['version'], 'quote_fingerprint' => $quote['quote_fingerprint'],
                'payments' => $quote['total'] === '0.00' ? [] : [['method_id' => DB::table('payment_methods')->where('code', 'TRANSFER')->value('id'), 'amount' => $quote['total']]]]);
        }
        $before = SalesReturnFixture::fingerprint();
        $this->actingAs(User::factory()->create()->givePermissionTo('sales.returns.view'));
        $rows = $this->getJson('/sales/returns/data')->assertOk()->json('data');
        $zeroRow = collect($rows)->firstWhere('id', $zero);
        $positiveRow = collect($rows)->firstWhere('id', $positive);
        $this->assertTrue($zeroRow['refund_available']);
        $this->assertSame('no_payment', $zeroRow['settlement']['outcome']);
        $this->assertSame('0.00', $zeroRow['settlement']['total']);
        $this->assertSame([], $zeroRow['settlement']['payments']);
        $this->assertSame('refunded', $positiveRow['settlement']['outcome']);
        $this->assertSame('0.01', $positiveRow['settlement']['total']);
        $this->assertSame('Transfer', $positiveRow['settlement']['payments'][0]['method_name']);
        foreach (['request_hash', 'quote_fingerprint', 'settled_by', 'account_number', 'total_cost', 'scope_revision'] as $key) {
            $this->assertStringNotContainsString($key, json_encode($rows));
        }
        $this->assertDatabaseCount('payment_refunds', 1);
        $this->assertSame($before, SalesReturnFixture::fingerprint());
    }

    public function test_list_distinguishes_pending_schema_from_unsettled_posted_return(): void
    {
        $this->seed(ReturnsAccessSeeder::class);
        ReturnRefundFixture::posted(ReturnRefundFixture::invoice());
        $this->actingAs(User::factory()->create()->givePermissionTo('sales.returns.view'));
        $this->getJson('/sales/returns/data')->assertOk()->assertJsonPath('data.0.refund_available', true)->assertJsonPath('data.0.settlement', null);
        Schema::drop('sales_return_refund_allocations');
        Schema::drop('sales_return_settlements');
        Schema::drop('sales_return_refund_scopes');
        $this->getJson('/sales/returns/data')->assertOk()->assertJsonPath('data.0.refund_available', false)->assertJsonPath('data.0.settlement', null);
    }
}
