<?php

namespace Tests\Feature\Modules\Reporting\OperationalReports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\Fixtures\ReturnRefundFixture;
use Tests\TestCase;

class OperationalReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_uses_real_posted_totals_and_warehouse_filter_with_global_cash_explicit(): void
    {
        $invoice = ReturnRefundFixture::invoice();
        ReturnRefundFixture::posted($invoice, '0.5');
        Permission::findOrCreate('reporting.operational.view', 'web');
        $this->actingAs(User::factory()->create()->givePermissionTo('reporting.operational.view'));
        $url = '/reports/operations/data?'.http_build_query(['from' => now('Asia/Jakarta')->toDateString(), 'to' => now('Asia/Jakarta')->toDateString()]);
        $all = $this->getJson($url)->assertOk()->assertJsonPath('sales.total', '1235100.00')->assertJsonPath('sales.count', 2)
            ->assertJsonPath('sales_returns.count', 1)->assertJsonPath('payments.incoming', '1235100.000000')->assertJsonPath('payments.scope', 'all_warehouses')->json();
        $emptyPage = $this->getJson($url.'&per_page=10&page=2')->assertOk()->assertJsonCount(0, 'stock.rows')->json();
        $this->assertSame($all['stock']['total_value'], $emptyPage['stock']['total_value']);
        $this->assertSame($all['stock']['count'], $emptyPage['stock']['count']);
        $this->assertSame($all['sales'], $emptyPage['sales']);
        $this->assertSame($all['payments'], $emptyPage['payments']);
        $other = DB::table('warehouses')->where('id', '!=', $invoice['warehouse_id'])->value('id');
        $this->getJson($url.'&warehouse_id='.$other)->assertOk()->assertJsonPath('sales.total', '0.00')->assertJsonPath('sales_returns.count', 0);
        $this->getJson('/reports/operations/data?from=2020-01-01&to=2026-01-01')->assertUnprocessable();
        $this->actingAs(User::factory()->create())->getJson($url)->assertForbidden();
    }
}
