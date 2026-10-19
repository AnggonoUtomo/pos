<?php

namespace Tests\Feature\Modules\Finance\Payments;

use App\Models\User;
use App\Modules\Finance\Payments\Application\Contracts\SaleRefundPaymentWriter;
use App\Modules\Finance\Payments\Application\DTOs\SaleRefundPaymentRequest;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fixtures\PaymentHistoryFixture;
use Tests\TestCase;

class RefundHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_refund_is_distinct_readonly_snapshot_outgoing_with_exact_precision_and_filters(): void
    {
        $this->withoutVite();
        $this->seed(PaymentsAccessSeeder::class);
        $fixture = PaymentHistoryFixture::seed();
        $refund = DB::transaction(fn () => app(SaleRefundPaymentWriter::class)->pay(new SaleRefundPaymentRequest(
            strtolower((string) Str::ulid()), 'SR-QA!%_', '9999999999999999999999999999.99',
            [['method_id' => $fixture['methods']['DEBIT'], 'amount' => '9999999999999999999999999999.99']],
        )));
        DB::table('payment_refunds')->where('id', $refund->id)->update(['paid_at' => '2026-10-16 10:00:00']);
        $url = '/finance/payments/history';
        $this->getJson($url.'/refunds/'.$refund->id)->assertUnauthorized();
        $this->actingAs(User::factory()->create()->givePermissionTo('finance.payments.manage'))
            ->getJson($url.'/refunds/'.$refund->id)->assertForbidden();
        $this->actingAs(User::factory()->create()->givePermissionTo('finance.payments.history.view'));
        $before = hash('sha256', json_encode(DB::table('payment_refunds')->get()));
        $rows = $this->getJson($url.'/data')->assertOk()->assertJsonPath('meta.total', 4)->json('data');
        $row = collect($rows)->firstWhere('id', $refund->id);
        $this->assertSame('sale_return', $row['source_type']);
        $this->assertSame('outgoing', $row['direction']);
        $this->assertSame('9999999999999999999999999999.99', $row['total']);
        $this->getJson($url.'/outgoing/'.$fixture['outgoing'])->assertOk()->assertJsonPath('total', '123.123456')->assertJsonPath('source_type', 'purchase');
        $this->getJson($url.'/incoming/'.$fixture['incoming'])->assertOk()->assertJsonPath('total', '10000.12')->assertJsonPath('source_type', 'sale');
        foreach ([['direction' => 'outgoing', 'total' => 2], ['search' => 'SR-QA!%_', 'total' => 1],
            ['date_from' => '2026-10-16', 'date_to' => '2026-10-16', 'total' => 3],
            ['account_id' => $fixture['cash_account'], 'method_id' => $fixture['methods']['TRANSFER'], 'total' => 0],
            ['account_id' => $fixture['bank_account'], 'method_id' => $fixture['methods']['TRANSFER'], 'total' => 2],
            ['account_id' => $fixture['bank_account'], 'method_id' => $fixture['methods']['DEBIT'], 'total' => 1]] as $filter) {
            $total = $filter['total'];
            unset($filter['total']);
            $this->getJson($url.'/data?'.http_build_query($filter))->assertOk()->assertJsonPath('meta.total', $total);
        }
        DB::table('payment_methods')->update(['name' => 'Changed', 'is_active' => false, 'deleted_at' => now()]);
        DB::table('payment_accounts')->update(['name' => 'Changed', 'is_active' => false, 'deleted_at' => now(), 'account_number' => 'PRIVATE-QA']);
        $detail = $this->getJson($url.'/refunds/'.strtoupper($refund->id))->assertOk()->assertJsonPath('source_type', 'sale_return')
            ->assertJsonPath('payments.0.method_name', 'Debit')->assertJsonPath('payments.0.amount', $row['total'])->assertJsonPath('tendered', null)->json();
        foreach (['request_hash', 'account_number', 'bank_name', 'account_holder', 'paid_by', 'total_cost', 'PRIVATE-QA'] as $key) {
            $this->assertStringNotContainsString($key, json_encode([$rows, $detail]));
        }
        $this->get($url)->assertOk()->assertInertia(fn (Assert $p) => $p->has('options.methods', 3)->has('options.accounts', 2));
        $this->getJson($url.'/outgoing/'.$refund->id)->assertNotFound();
        $this->getJson($url.'/refunds/'.$fixture['outgoing'])->assertNotFound();
        $this->postJson($url.'/refunds/'.$refund->id, [])->assertStatus(405);
        $this->deleteJson($url.'/refunds/'.$refund->id)->assertStatus(405);
        $this->assertSame($before, hash('sha256', json_encode(DB::table('payment_refunds')->get())));
    }

    public function test_legacy_history_works_before_refund_schema_activation(): void
    {
        $this->withoutVite();
        $this->seed(PaymentsAccessSeeder::class);
        PaymentHistoryFixture::seed();
        Schema::drop('payment_refund_lines');
        Schema::drop('payment_refunds');
        $this->actingAs(User::factory()->create()->givePermissionTo('finance.payments.history.view'));
        $this->getJson('/finance/payments/history/data')->assertOk()->assertJsonPath('meta.total', 3);
        $this->get('/finance/payments/history')->assertOk();
        $this->getJson('/finance/payments/history/refunds/'.strtolower((string) Str::ulid()))->assertNotFound();
    }
}
