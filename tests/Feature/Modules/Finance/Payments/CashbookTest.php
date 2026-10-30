<?php

namespace Tests\Feature\Modules\Finance\Payments;

use App\Models\User;
use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;
use App\Modules\Finance\Payments\Application\Contracts\PurchaseRefundReceiptWriter;
use App\Modules\Finance\Payments\Application\Contracts\SaleRefundPaymentWriter;
use App\Modules\Finance\Payments\Application\DTOs\PurchaseRefundReceiptRequest;
use App\Modules\Finance\Payments\Application\DTOs\SaleRefundPaymentRequest;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\Fixtures\PaymentHistoryFixture;
use Tests\TestCase;

class CashbookTest extends TestCase
{
    use RefreshDatabase;

    private array $fixture;

    private const URL = '/finance/cashbook';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.timezone' => 'UTC']);
        $this->travelTo(now()->setDate(2026, 10, 28)->setTime(8, 0));
        $this->seed(PaymentsAccessSeeder::class);
        $this->fixture = PaymentHistoryFixture::seed();
        $user = User::factory()->create();
        foreach (['view', 'manage', 'open', 'reverse'] as $action) {
            $user->givePermissionTo(Permission::findOrCreate('finance.cashbook.'.$action, 'web'));
        }
        $this->actingAs($user);
    }

    private function opening(string $account, string $amount = '100', string $date = '2026-10-16'): void
    {
        $this->postJson(self::URL.'/openings', ['account_id' => $account, 'amount' => $amount, 'starts_on' => $date])->assertSuccessful();
    }

    private function statement(string $account, array $filters = []): array
    {
        return $this->getJson(self::URL.'/data?'.http_build_query(['account_id' => $account] + $filters))->assertOk()->json();
    }

    private function payload(string $kind = 'outgoing', string $amount = '10.123456'): array
    {
        return ['kind' => $kind, 'account_id' => $this->fixture['cash_account'], 'amount' => $amount,
            'occurred_on' => '2026-10-28', 'description' => 'Biaya operasional CCTV', 'idempotency_key' => (string) Str::uuid()];
    }

    public function test_cutover_per_account_uses_net_cash_and_keeps_prior_balance_in_filtered_period(): void
    {
        $cash = $this->fixture['cash_account'];
        $bank = $this->fixture['bank_account'];
        $this->assertNull($this->statement($cash)['opening']);
        $this->opening($cash);
        $this->opening($bank, '200');
        $result = $this->statement($cash);
        $this->assertSame('4500.120000', $result['summary']['closing']);
        $this->assertSame('4400.120000', $result['summary']['incoming']);
        $this->assertSame('6076.876544', $this->statement($bank)['summary']['closing']);
        $period = $this->statement($cash, ['date_from' => '2026-10-17', 'date_to' => '2026-10-17']);
        $this->assertSame('4100.120000', $period['summary']['opening']);
        $this->assertSame('400.000000', $period['summary']['incoming']);
        $this->assertSame('4500.120000', $period['rows'][0]['balance']);
        $this->assertSame(2, DB::table('payment_receipts')->count());
        $this->assertSame(1, DB::table('payment_disbursements')->count());
    }

    public function test_opening_is_unique_immutable_and_cutoff_wib_excludes_old_transactions(): void
    {
        $cash = $this->fixture['cash_account'];
        $this->opening($cash, '50', '2026-10-17');
        $this->opening($cash, '50.000000', '2026-10-17');
        $this->assertDatabaseCount('cashbook_openings', 1);
        $this->postJson(self::URL.'/openings', ['account_id' => $cash, 'amount' => '55', 'starts_on' => '2026-10-17'])->assertUnprocessable();
        $this->assertSame('450.000000', $this->statement($cash)['summary']['closing']);
        $this->assertSame(1, $this->statement($cash)['meta']['total']);
        $this->putJson(self::URL.'/openings', [])->assertStatus(405);
    }

    public function test_transfer_and_reversal_are_atomic_exact_idempotent_and_do_not_rewrite_source(): void
    {
        foreach (['cash_account', 'bank_account'] as $key) {
            $this->opening($this->fixture[$key], '0', '2026-10-28');
        }
        $payload = $this->payload('transfer') + ['counter_account_id' => $this->fixture['bank_account']];
        $id = $this->postJson(self::URL.'/entries', $payload)->assertSuccessful()->json('id');
        $before = (array) DB::table('cashbook_entries')->where('id', $id)->first();
        $this->postJson(self::URL.'/entries', $payload)->assertOk()->assertJsonPath('id', $id);
        $this->postJson(self::URL.'/entries', array_replace($payload, ['amount' => '99']))->assertStatus(409);
        $this->assertSame('-10.123456', $this->statement($this->fixture['cash_account'])['summary']['closing']);
        $this->assertTrue($this->statement($this->fixture['cash_account'])['summary']['negative']);
        $this->assertSame('10.123456', $this->statement($this->fixture['bank_account'])['summary']['closing']);
        $reverse = $this->postJson(self::URL.'/entries/'.$id.'/reverse', ['reason' => 'Salah akun tujuan'])->assertSuccessful()->json('id');
        $this->postJson(self::URL.'/entries/'.$id.'/reverse', ['reason' => 'Salah akun tujuan'])->assertOk()->assertJsonPath('id', $reverse);
        $this->postJson(self::URL.'/entries/'.$reverse.'/reverse', ['reason' => 'Tidak boleh'])->assertUnprocessable();
        foreach (['cash_account', 'bank_account'] as $key) {
            $this->assertSame('0.000000', $this->statement($this->fixture[$key])['summary']['closing']);
        }
        $this->assertSame($before, (array) DB::table('cashbook_entries')->where('id', $id)->first());
        $this->assertDatabaseCount('cashbook_entries', 2);
        $this->assertSame(4, DB::table('activity_log')->where('log_name', 'finance.cashbook')->count());
    }

    public function test_invalid_inputs_permissions_and_uninitialised_accounts_are_rejected(): void
    {
        $this->postJson(self::URL.'/entries', $this->payload())->assertUnprocessable();
        $this->opening($this->fixture['cash_account'], '0', '2026-10-28');
        foreach ([['amount' => '0'], ['amount' => '-1'], ['amount' => '1.1234567'], ['amount' => '1000000000000000000000000'], ['occurred_on' => '2026-10-27'], ['occurred_on' => '2026-10-29'], ['kind' => 'transfer', 'counter_account_id' => $this->fixture['cash_account']]] as $patch) {
            $this->postJson(self::URL.'/entries', array_replace($this->payload(), $patch))->assertUnprocessable();
        }
        $this->postJson(self::URL.'/entries', $this->payload('transfer') + ['counter_account_id' => $this->fixture['bank_account']])->assertUnprocessable();
        $this->actingAs(User::factory()->create()->givePermissionTo('finance.cashbook.view'));
        $this->getJson(self::URL.'/data')->assertOk();
        $this->postJson(self::URL.'/entries', $this->payload())->assertForbidden();
        $this->postJson(self::URL.'/openings', [])->assertForbidden();
        $this->postJson(self::URL.'/entries/'.strtolower((string) Str::ulid()).'/reverse', [])->assertForbidden();
        $this->assertDatabaseCount('cashbook_entries', 0);
    }

    public function test_audit_failure_rolls_back_and_opening_blocks_account_deactivation(): void
    {
        $cash = $this->fixture['cash_account'];
        $this->opening($cash, '0', '2026-10-28');
        $this->assertTrue(app(PaymentsRepository::class)->isAccountReferenced($cash));
        Activity::creating(fn () => throw new \RuntimeException('QA audit failure'));
        try {
            $this->postJson(self::URL.'/entries', $this->payload())->assertServerError();
            $this->assertDatabaseCount('cashbook_entries', 0);
        } finally {
            Activity::flushEventListeners();
        }
    }

    public function test_both_refund_ledgers_are_read_once_using_their_actual_accounts(): void
    {
        $id = fn () => strtolower((string) Str::ulid());
        DB::transaction(fn () => app(SaleRefundPaymentWriter::class)->pay(
            new SaleRefundPaymentRequest($id(), 'SR-CASHBOOK', '5.25', [['method_id' => $this->fixture['methods']['TUNAI'], 'amount' => '5.25']])
        ));
        $purchase = DB::table('payment_disbursements')->where('id', $this->fixture['outgoing'])->first();
        DB::transaction(fn () => app(PurchaseRefundReceiptWriter::class)->receive(
            new PurchaseRefundReceiptRequest($id(), 'PR-CASHBOOK', $purchase->source_id, $purchase->id, '0.123456', [['method_id' => $this->fixture['methods']['TUNAI'], 'amount' => '0.123456']])
        ));
        $this->opening($this->fixture['cash_account']);
        $result = $this->statement($this->fixture['cash_account']);
        $this->assertSame('4494.993456', $result['summary']['closing']);
        $this->assertSame(4, $result['meta']['total']);
        $this->assertContains('sale_return', array_column($result['rows'], 'kind'));
        $this->assertContains('purchase_return', array_column($result['rows'], 'kind'));
    }

    public function test_large_amounts_remain_exact_and_pagination_carries_prior_balance(): void
    {
        $this->opening($this->fixture['cash_account'], '0', '2026-10-28');
        $this->postJson(self::URL.'/entries', $this->payload('incoming', '9007199254740993.123456'))->assertOk();
        for ($i = 0; $i < 27; $i++) {
            $this->postJson(self::URL.'/entries', $this->payload('incoming', '1.000001'))->assertOk();
        }
        $page = $this->statement($this->fixture['cash_account'], ['page' => 2, 'per_page' => 25]);
        $this->assertCount(3, $page['rows']);
        $this->assertSame(28, $page['meta']['total']);
        $this->assertSame('9007199254741020.123483', $page['summary']['closing']);
        $this->assertSame($page['summary']['closing'], $page['rows'][2]['balance']);
        $this->assertSame('9007199254741018.123481', $page['rows'][0]['balance']);
        $smaller = $this->statement($this->fixture['cash_account'], ['page' => 3, 'per_page' => 10]);
        $this->assertSame($page['summary'], $smaller['summary']);
        $this->assertCount(8, $smaller['rows']);
    }
}
