<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Reporting\OperationalReports\Application\Queries\OperationalReport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\ReturnRefundFixture;
use Tests\Fixtures\SalesReturnFixture;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->actingAs($user = User::factory()->create());

        $this->get('/dashboard')->assertOk();
    }

    public function test_user_without_permissions_receives_no_business_data(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')->where('summary', null)->where('recent_sales', null)->where('sales_scope', null));
    }

    public function test_super_system_gets_real_zero_totals_when_database_is_empty(): void
    {
        $user = User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('summary.sales.count', 0)->where('summary.sales.total', '0.00')
            ->where('summary.purchases.count', 0)->where('summary.purchases.total_value', '0.000000')
            ->where('summary.payments.incoming', '0.000000')->where('summary.payments.outgoing', '0.000000')
            ->where('summary.stock.count', 0)->where('summary.stock.total_value', '0.000000')
            ->has('recent_sales', 0)->where('sales_scope', 'all'));
    }

    public function test_dashboard_matches_real_report_and_does_not_mutate_ledgers(): void
    {
        $invoice = ReturnRefundFixture::invoice();
        ReturnRefundFixture::posted($invoice, '0.5');
        $before = SalesReturnFixture::fingerprint();
        $expected = app(OperationalReport::class)->handle([]);
        $this->actingAs($this->operator(['reporting.operational.view', 'sales.pos.invoices.view']))
            ->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('summary.sales.total', $expected['sales']['total'])->where('summary.sales.count', 2)
            ->where('summary.purchases.total_value', $expected['purchases']['total_value'])
            ->where('summary.sales_returns.count', 1)->where('summary.purchase_returns.count', 0)
            ->where('summary.payments', $expected['payments'])
            ->where('summary.stock.total_value', $expected['stock']['total_value'])
            ->where('summary.stock.count', $expected['stock']['count'])
            ->where('summary.date', now('Asia/Jakarta')->toDateString())
            ->has('recent_sales', 2)->where('sales_scope', 'all')->missing('summary.stock.rows')->missing('summary.sales.total_cost'));
        $this->assertSame($before, SalesReturnFixture::fingerprint());
    }

    public function test_cashier_only_sees_own_invoices_and_report_permission_does_not_grant_invoice_access(): void
    {
        $invoice = ReturnRefundFixture::invoice();
        $actorId = DB::table('sales_invoices')->where('id', $invoice['id'])->value('actor_id');
        $cashier = User::findOrFail($actorId);
        Permission::findOrCreate('sales.pos.invoices.post', 'web');
        $cashier->givePermissionTo('sales.pos.invoices.post');
        $this->actingAs($cashier)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('summary', null)->where('sales_scope', 'own')->has('recent_sales', 1)->where('recent_sales.0.id', $invoice['id']));
        $this->actingAs($this->operator(['reporting.operational.view']))->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('summary.sales.count', 2)->where('recent_sales', null)->where('sales_scope', null));
        $this->actingAs($this->operator(['sales.pos.invoices.view']))->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('summary', null)->has('recent_sales', 2)->where('sales_scope', 'all'));
    }

    public function test_today_uses_wib_boundaries_with_exclusive_end_and_recent_sales_are_limited(): void
    {
        config(['app.timezone' => 'UTC']);
        $this->travelTo(Carbon::parse('2026-10-25T00:30:00+07:00'));
        $invoice = ReturnRefundFixture::invoice();
        $other = DB::table('sales_invoices')->where('id', '!=', $invoice['id'])->value('id');
        DB::table('sales_invoices')->where('id', $invoice['id'])->update(['posted_at' => '2026-10-24 17:00:00']);
        DB::table('sales_invoices')->where('id', $other)->update(['posted_at' => '2026-10-25 17:00:00']);
        $this->actingAs($this->operator(['reporting.operational.view']))->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('summary.date', '2026-10-25')->where('summary.sales.count', 1)->where('summary.sales.total', '100.00'));
        DB::table('sales_invoices')->where('id', $invoice['id'])->update(['posted_at' => '2026-10-24 16:59:59']);
        $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('summary.sales.count', 0));

        // Copy only the disposable fixture header to exercise the bounded read list.
        $source = (array) DB::table('sales_invoices')->where('id', $invoice['id'])->first();
        for ($i = 0; $i < 5; $i++) {
            DB::table('sales_invoices')->insert(array_replace($source, ['id' => strtolower((string) Str::ulid()),
                'idempotency_key' => strtolower((string) Str::ulid()), 'number' => 'QA-RECENT-'.$i]));
        }
        $this->actingAs($this->operator(['sales.pos.invoices.view']))->get('/dashboard')->assertInertia(fn (Assert $page) => $page->has('recent_sales', 5));
    }

    private function operator(array $permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        return User::factory()->create()->givePermissionTo($permissions);
    }
}
