<?php

namespace Tests\Feature\Modules\Sales\Returns;

use App\Models\User;
use App\Modules\Sales\Returns\Application\Actions\ManageReturnDraft;
use App\Modules\Sales\Returns\Database\Seeders\ReturnsAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\SalesReturnFixture;
use Tests\TestCase;

class PostReturnDraftTest extends TestCase
{
    use RefreshDatabase;

    private array $invoice;

    private User $poster;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(ReturnsAccessSeeder::class);
        $this->invoice = SalesReturnFixture::seed();
        $this->poster = User::factory()->create()->givePermissionTo(['sales.returns.view', 'sales.returns.post']);
        $this->actingAs($this->poster);
    }

    private function draft(string $quantity = '1'): string
    {
        return app(ManageReturnDraft::class)->save(['invoice_id' => $this->invoice['id'],
            'lines' => [['invoice_line_id' => $this->invoice['lines'][0]['id'], 'quantity' => $quantity]]]);
    }

    public function test_posting_is_atomic_readonly_and_exact_replay_works_after_master_inactivation(): void
    {
        $id = $this->draft();
        $source = SalesReturnFixture::sourceFingerprint();
        $first = $this->postJson('/sales/returns/'.$id.'/post', ['version' => 1])->assertOk()->assertJsonPath('status', 'posted')->assertJsonPath('version', 2)->json();
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $this->invoice['warehouse_id'], 'item_id' => $this->invoice['lines'][0]['item_id'], 'quantity' => '7.000000']);
        $this->assertDatabaseCount('stock_return_allocations', 1);
        $this->assertSame($source, SalesReturnFixture::sourceFingerprint());
        $before = SalesReturnFixture::fingerprint();
        $audit = Activity::count();
        DB::table('warehouses')->where('id', $this->invoice['warehouse_id'])->update(['is_active' => false]);
        $this->postJson('/sales/returns/'.$id.'/post', ['version' => 1])->assertOk()->assertExactJson($first);
        $this->assertSame($before, SalesReturnFixture::fingerprint());
        $this->assertSame($audit, Activity::count());
        $this->postJson('/sales/returns/'.$id.'/post', ['version' => 2])->assertUnprocessable();
        $this->actingAs(User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web')));
        $this->putJson('/sales/returns/'.$id, ['invoice_id' => $this->invoice['id'], 'version' => 2, 'lines' => [['invoice_line_id' => $this->invoice['lines'][0]['id'], 'quantity' => '1']]])->assertUnprocessable();
        $this->deleteJson('/sales/returns/'.$id, ['version' => 2])->assertUnprocessable();
        $rows = $this->getJson('/sales/returns/data')->assertOk()->json('data');
        $json = json_encode($rows);
        foreach (['total_cost', 'total_value', 'request_hash', 'posted_by'] as $private) {
            $this->assertStringNotContainsString($private, $json);
        }
    }

    public function test_two_drafts_share_cumulative_limit_and_failure_has_no_partial_ledger(): void
    {
        $first = $this->draft('1.5');
        $second = $this->draft('1');
        $this->postJson('/sales/returns/'.$first.'/post', ['version' => 1])->assertOk();
        $before = SalesReturnFixture::fingerprint();
        $audit = Activity::count();
        $this->postJson('/sales/returns/'.$second.'/post', ['version' => 1])->assertUnprocessable();
        $this->assertDatabaseHas('sales_returns', ['id' => $second, 'status' => 'draft', 'version' => 1]);
        $this->assertSame($before, SalesReturnFixture::fingerprint());
        $this->assertSame($audit, Activity::count());
    }

    public function test_permission_is_separate_from_manage_and_stale_deleted_unknown_are_rejected(): void
    {
        $id = $this->draft();
        $manager = User::factory()->create()->givePermissionTo(['sales.returns.view', 'sales.returns.manage']);
        $this->actingAs($manager)->postJson('/sales/returns/'.$id.'/post', ['version' => 1])->assertForbidden();
        $this->actingAs($this->poster)->postJson('/sales/returns/'.$id.'/post', ['version' => 2])->assertUnprocessable();
        $this->postJson('/sales/returns/'.$id.'/post', [])->assertUnprocessable();
        app(ManageReturnDraft::class)->lifecycle($id, 1, false);
        $this->postJson('/sales/returns/'.$id.'/post', ['version' => 2])->assertNotFound();
        $this->postJson('/sales/returns/'.strtolower((string) Str::ulid()).'/post', ['version' => 1])->assertNotFound();
    }

    public function test_returns_audit_veto_rolls_back_successful_stock_writer(): void
    {
        $id = $this->draft();
        $before = SalesReturnFixture::fingerprint();
        Activity::saving(fn ($log) => $log->log_name === 'sales.returns' && $log->event === 'posted' ? false : null);
        try {
            $this->withoutExceptionHandling();
            $this->postJson('/sales/returns/'.$id.'/post', ['version' => 1]);
            $this->fail('Audit veto harus membatalkan receipt.');
        } catch (\RuntimeException) {
            $this->assertSame($before, SalesReturnFixture::fingerprint());
            $this->assertDatabaseCount('stock_return_allocations', 0);
            $this->assertDatabaseHas('sales_returns', ['id' => $id, 'status' => 'draft', 'version' => 1, 'stock_receipt_id' => null]);
        } finally {
            Activity::flushEventListeners();
        }
    }

    public function test_tampered_base_quantity_or_inactive_master_cannot_be_posted(): void
    {
        $id = $this->draft();
        DB::table('sales_return_lines')->where('sales_return_id', $id)->update(['base_quantity' => '1.500000']);
        $this->postJson('/sales/returns/'.$id.'/post', ['version' => 1])->assertUnprocessable();
        DB::table('sales_return_lines')->where('sales_return_id', $id)->update(['base_quantity' => '1.000000']);
        DB::table('items')->where('id', $this->invoice['lines'][0]['item_id'])->update(['is_active' => false]);
        $this->postJson('/sales/returns/'.$id.'/post', ['version' => 1])->assertUnprocessable();
        $this->assertDatabaseCount('stock_return_allocations', 0);
    }
}
