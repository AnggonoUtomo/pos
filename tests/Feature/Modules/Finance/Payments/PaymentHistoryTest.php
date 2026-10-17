<?php

namespace Tests\Feature\Modules\Finance\Payments;

use App\Models\User;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\PaymentHistoryFixture;
use Tests\TestCase;

class PaymentHistoryTest extends TestCase
{
    use RefreshDatabase;

    private array $fixture;

    private User $viewer;

    private const URL = '/finance/payments/history';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.timezone' => 'UTC']);
        $this->seed(PaymentsAccessSeeder::class);
        $this->fixture = PaymentHistoryFixture::seed();
        $this->viewer = User::factory()->create()->givePermissionTo(Permission::findOrCreate('finance.payments.history.view', 'web'));
    }

    public function test_permission_is_dedicated_and_all_endpoints_readonly_private(): void
    {
        $this->get(self::URL)->assertRedirect('/login');
        foreach (['finance.payments.view', 'finance.payments.manage'] as $permission) {
            $user = User::factory()->create()->givePermissionTo($permission);
            $this->actingAs($user)->getJson(self::URL.'/data')->assertForbidden();
            $this->getJson(self::URL.'/incoming/'.$this->fixture['incoming'])->assertForbidden();
        }
        $before = PaymentHistoryFixture::fingerprint();
        $this->actingAs($this->viewer)->get(self::URL)->assertOk()->assertInertia(fn (Assert $p) => $p->component('finance/payments/history/index', false)->has('records.data', 3)->has('options.accounts', 2)->has('options.methods', 2));
        $rows = $this->getJson(self::URL.'/data')->assertOk()->assertJsonPath('meta.total', 3)->json('data');
        $this->assertSame('incoming', $rows[0]['direction']);
        $incoming = $this->getJson(self::URL.'/incoming/'.$this->fixture['incoming'])->assertOk()->assertJsonPath('total', '10000.12')->assertJsonPath('tendered', '11000.12')->assertJsonPath('change', '1000.00')->json();
        $this->assertSame('4000.12', collect($incoming['payments'])->firstWhere('method_type', 'cash')['amount']);
        $outgoing = $this->getJson(self::URL.'/outgoing/'.$this->fixture['outgoing'])->assertOk()->assertJsonPath('total', '123.123456')->assertJsonPath('tendered', null)->json();
        $public = json_encode([$rows, $incoming, $outgoing]);
        foreach (['request_hash', 'account_number', 'bank_name', 'account_holder', 'posted_by', 'paid_by'] as $key) {
            $this->assertStringNotContainsString($key, $public);
        }
        $this->assertSame($before, PaymentHistoryFixture::fingerprint());
        $this->postJson(self::URL, [])->assertStatus(405);
        $this->deleteJson(self::URL.'/incoming/'.$this->fixture['incoming'])->assertStatus(405);
    }

    public function test_filters_literal_search_jakarta_boundaries_and_same_split_line(): void
    {
        $this->actingAs($this->viewer);
        foreach ([['direction' => 'incoming', 'total' => 2], ['direction' => 'outgoing', 'total' => 1], ['search' => '%_', 'total' => 1], ['date_from' => '2026-10-16', 'date_to' => '2026-10-16', 'total' => 2],
            ['account_id' => $this->fixture['bank_account'], 'method_id' => $this->fixture['methods']['TUNAI'], 'total' => 0], ['account_id' => $this->fixture['bank_account'], 'method_id' => $this->fixture['methods']['TRANSFER'], 'total' => 2]] as $case) {
            $count = $case['total'];
            unset($case['total']);
            $this->getJson(self::URL.'/data?'.http_build_query($case))->assertOk()->assertJsonPath('meta.total', $count);
        }
        foreach (['direction=invalid', 'date_from=2026-02-30', 'date_from=2026-10-17&date_to=2026-10-16', 'page=0', 'account_id=invalid', 'actor_id=1', 'search[]=x'] as $query) {
            $this->getJson(self::URL.'/data?'.$query)->assertUnprocessable();
        }
        $this->getJson(self::URL.'/incoming/'.$this->fixture['outgoing'])->assertNotFound();
        $this->getJson(self::URL.'/outgoing/'.strtolower((string) Str::ulid()))->assertNotFound();
        $this->getJson(self::URL.'/other/'.$this->fixture['incoming'])->assertNotFound();
    }

    public function test_snapshot_choices_survive_master_changes_and_super_does_not_skip_validation(): void
    {
        DB::table('payment_methods')->update(['name' => 'Changed', 'deleted_at' => now(), 'is_active' => false]);
        DB::table('payment_accounts')->update(['name' => 'Changed bank', 'deleted_at' => now(), 'is_active' => false, 'account_number' => 'PRIVATE-QA']);
        $this->actingAs($this->viewer)->get(self::URL)->assertOk()->assertInertia(fn (Assert $p) => $p->has('options.accounts', 2)->has('options.methods', 2));
        $this->getJson(self::URL.'/outgoing/'.$this->fixture['outgoing'])->assertOk()->assertJsonPath('payments.0.method_name', 'Transfer')->assertDontSee('Changed')->assertDontSee('PRIVATE-QA');
        $super = User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->actingAs($super)->getJson(self::URL.'/data')->assertOk();
        $this->getJson(self::URL.'/data?direction=bad')->assertUnprocessable();
    }

    public function test_pagination_is_deterministic_and_amounts_are_not_aggregated(): void
    {
        $sample = (array) DB::table('payment_receipts')->where('id', $this->fixture['next'])->first();
        for ($i = 0; $i < 25; $i++) {
            $copy = $sample;
            $copy['id'] = strtolower((string) Str::ulid());
            $copy['source_id'] = strtolower((string) Str::ulid());
            DB::table('payment_receipts')->insert($copy);
        }
        $this->actingAs($this->viewer);
        $one = $this->getJson(self::URL.'/data')->assertJsonPath('meta.total', 28)->assertJsonCount(25, 'data')->json('data');
        $two = $this->getJson(self::URL.'/data?page=2')->assertJsonCount(3, 'data')->json('data');
        $this->assertSame([], array_intersect(array_column($one, 'id'), array_column($two, 'id')));
        $this->assertSame($one, $this->getJson(self::URL.'/data')->json('data'));
    }
}
