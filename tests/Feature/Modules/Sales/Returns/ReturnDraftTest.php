<?php

namespace Tests\Feature\Modules\Sales\Returns;

use App\Models\User;
use App\Modules\Sales\Returns\Application\Actions\ManageReturnDraft;
use App\Modules\Sales\Returns\Database\Seeders\ReturnsAccessSeeder;
use App\Modules\Sales\Returns\Database\Seeders\ReturnsDemoSeeder;
use App\Modules\Sales\Returns\Infrastructure\Persistence\Models\SalesReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\SalesReturnFixture;
use Tests\TestCase;

class ReturnDraftTest extends TestCase
{
    use RefreshDatabase;

    private array $invoice;

    private User $manager;

    private array $input;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(ReturnsAccessSeeder::class);
        $this->invoice = SalesReturnFixture::seed();
        $this->manager = User::factory()->create()->givePermissionTo(['sales.returns.view', 'sales.returns.manage']);
        $this->input = ['invoice_id' => $this->invoice['id'], 'notes' => 'Barang diperiksa',
            'lines' => [['invoice_line_id' => $this->invoice['lines'][0]['id'], 'quantity' => '1']]];
        $this->actingAs($this->manager);
    }

    public function test_lifecycle_is_versioned_audited_and_never_changes_source_ledgers(): void
    {
        $hash = SalesReturnFixture::fingerprint();
        $id = $this->postJson('/sales/returns', $this->input)->assertCreated()->json('id');
        $this->assertDatabaseHas('sales_returns', ['id' => $id, 'status' => 'draft', 'version' => 1]);
        $this->putJson('/sales/returns/'.$id, $this->input + ['version' => 1])->assertOk();
        $this->assertDatabaseHas('sales_returns', ['id' => $id, 'version' => 1]);
        $this->input['lines'][0]['quantity'] = '0.5';
        $this->putJson('/sales/returns/'.$id, $this->input + ['version' => 1])->assertOk();
        $this->deleteJson('/sales/returns/'.$id, ['version' => 1])->assertUnprocessable();
        $this->deleteJson('/sales/returns/'.$id, ['version' => 2])->assertOk();
        $this->putJson('/sales/returns/'.$id, $this->input + ['version' => 3])->assertNotFound();
        $this->postJson('/sales/returns/'.$id.'/restore', ['version' => 3])->assertOk();
        $this->assertDatabaseHas('sales_returns', ['id' => $id, 'version' => 4, 'deleted_at' => null]);
        $this->assertSame(['created', 'updated', 'deleted', 'restored'], DB::table('activity_log')->where('log_name', 'sales.returns')->orderBy('id')->pluck('event')->all());
        $this->assertSame($hash, SalesReturnFixture::fingerprint());
    }

    public function test_snapshot_comes_from_original_invoice_not_input_or_current_masters(): void
    {
        DB::table('items')->where('id', $this->invoice['lines'][0]['item_id'])->update(['name' => 'Nama berubah', 'is_active' => false, 'deleted_at' => now()]);
        $source = $this->getJson('/sales/returns/invoices/'.$this->invoice['id'])->assertOk()->json();
        $this->assertSame('Kamera IP 2MP', $source['lines'][0]['name']);
        $this->assertStringNotContainsString('total_cost', json_encode($source));
        $this->assertArrayNotHasKey('payments', $source);
        $this->postJson('/sales/returns', $this->input + ['total' => '0', 'status' => 'posted'])->assertCreated();
        $record = $this->getJson('/sales/returns/data')->assertOk()->json('data.0');
        $this->assertSame('Kamera IP 2MP', $record['lines'][0]['snapshot']['name']);
        $this->assertSame($source['lines'][0]['unit_price'], $record['lines'][0]['snapshot']['unit_price']);
        $this->assertSame('draft', $record['status']);
    }

    public function test_quantity_duplicate_foreign_line_and_invalid_version_fail_without_writes(): void
    {
        foreach (['0', '-1', '2.000001', '1,2', '1e0', '0.0000001'] as $value) {
            $input = $this->input;
            $input['lines'][0]['quantity'] = $value;
            $this->postJson('/sales/returns', $input)->assertUnprocessable();
        }
        $input = $this->input;
        $input['lines'][] = $input['lines'][0];
        $this->postJson('/sales/returns', $input)->assertUnprocessable();
        $input['lines'] = [['invoice_line_id' => strtolower((string) Str::ulid()), 'quantity' => '1']];
        $this->postJson('/sales/returns', $input)->assertUnprocessable();
        $this->assertDatabaseCount('sales_returns', 0);
        $this->assertSame(0, DB::table('activity_log')->where('log_name', 'sales.returns')->count());
    }

    public function test_permissions_protect_source_lookup_and_mutation(): void
    {
        $viewer = User::factory()->create()->givePermissionTo('sales.returns.view');
        $this->actingAs($viewer)->getJson('/sales/returns/data')->assertOk();
        $this->getJson('/sales/returns/invoices')->assertForbidden();
        $this->getJson('/sales/returns/invoices/'.$this->invoice['id'])->assertForbidden();
        $this->postJson('/sales/returns', $this->input)->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson('/sales/returns/data')->assertForbidden();
        auth()->logout();
        $this->getJson('/sales/returns/data')->assertUnauthorized();
    }

    public function test_super_system_cannot_bypass_business_rules_or_version(): void
    {
        $super = User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->actingAs($super)->getJson('/sales/returns/invoices')->assertOk();
        $id = $this->postJson('/sales/returns', $this->input)->assertCreated()->json('id');
        $this->putJson('/sales/returns/'.$id, $this->input + ['version' => 0])->assertUnprocessable();
        $this->input['lines'][0]['quantity'] = '3';
        $this->putJson('/sales/returns/'.$id, $this->input + ['version' => 1])->assertUnprocessable();
        DB::table('sales_returns')->where('id', $id)->update(['status' => 'posted']);
        $this->deleteJson('/sales/returns/'.$id, ['version' => 1])->assertUnprocessable();
        $this->assertDatabaseHas('sales_returns', ['id' => $id, 'version' => 1, 'deleted_at' => null]);
    }

    public function test_invoice_is_immutable_and_foreign_price_fields_cannot_be_mass_assigned(): void
    {
        $id = $this->postJson('/sales/returns', $this->input)->assertCreated()->json('id');
        $input = $this->input;
        $input['invoice_id'] = strtolower((string) Str::ulid());
        $this->putJson('/sales/returns/'.$id, $input + ['version' => 1])->assertUnprocessable();
        $input = $this->input;
        $input['lines'][0]['unit_price'] = '0';
        $this->postJson('/sales/returns', $input)->assertUnprocessable();
        $this->postJson('/sales/returns', ['invoice_id' => $this->invoice['id'], 'lines' => []])->assertUnprocessable();
        $this->postJson('/sales/returns', $this->input + ['version' => 99, 'number' => 'FAKE', 'demo_key' => 'FAKE'])->assertCreated();
        $this->assertSame(0, DB::table('sales_returns')->where('number', 'FAKE')->orWhere('demo_key', 'FAKE')->count());
    }

    public function test_search_is_literal_bounded_and_all_gets_are_readonly(): void
    {
        $this->postJson('/sales/returns', $this->input)->assertCreated();
        $hash = SalesReturnFixture::fingerprint();
        $audit = Activity::count();
        $this->getJson('/sales/returns/data?search='.urlencode($this->invoice['number']))->assertOk()->assertJsonPath('meta.total', 1);
        foreach (['%', '_', "' OR 1=1 --"] as $literal) {
            $this->getJson('/sales/returns/data?'.http_build_query(['search' => $literal]))->assertOk()->assertJsonPath('meta.total', 0);
            $this->getJson('/sales/returns/invoices?'.http_build_query(['search' => $literal]))->assertOk()->assertExactJson([]);
        }
        $this->getJson('/sales/returns/data?search='.str_repeat('x', 151))->assertUnprocessable();
        $this->getJson('/sales/returns/data?page=-1')->assertUnprocessable();
        $this->getJson('/sales/returns/data?trashed=maybe')->assertUnprocessable();
        $this->getJson('/sales/returns/invoices/'.strtolower((string) Str::ulid()))->assertNotFound();
        $this->getJson('/sales/returns/invoices/invalid')->assertNotFound();
        $this->assertSame($audit, Activity::count());
        $this->assertSame($hash, SalesReturnFixture::fingerprint());
    }

    public function test_pagination_and_trashed_filter_never_drop_draft_lines(): void
    {
        for ($i = 0; $i < 26; $i++) {
            app(ManageReturnDraft::class)->save($this->input);
        }
        $page = $this->getJson('/sales/returns/data')->assertOk()->assertJsonPath('meta.total', 26)->assertJsonCount(25, 'data')->json();
        $this->getJson('/sales/returns/data?page=2')->assertOk()->assertJsonCount(1, 'data')->assertJsonCount(1, 'data.0.lines');
        $id = $page['data'][0]['id'];
        $this->deleteJson('/sales/returns/'.$id, ['version' => 1])->assertOk();
        $this->getJson('/sales/returns/data?trashed=1')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonCount(1, 'data.0.lines');
    }

    public function test_audit_failure_rolls_back_creation_and_lifecycle(): void
    {
        $id = app(ManageReturnDraft::class)->save($this->input);
        $before = DB::table('sales_returns')->where('id', $id)->first();
        $hash = SalesReturnFixture::fingerprint();
        Activity::saving(fn ($log) => $log->log_name === 'sales.returns' ? false : null);
        try {
            foreach (['create', 'update', 'delete'] as $operation) {
                try {
                    if ($operation === 'delete') {
                        app(ManageReturnDraft::class)->lifecycle($id, 1, false);
                    } else {
                        $input = array_replace($this->input, ['notes' => 'Ganti rahasia', 'version' => 1]);
                        app(ManageReturnDraft::class)->save($input, $operation === 'update' ? $id : null);
                    }
                    $this->fail('Audit veto harus membatalkan transaction.');
                } catch (RuntimeException) {
                    $this->assertSame(1, DB::table('sales_returns')->count());
                    $this->assertEquals($before, DB::table('sales_returns')->where('id', $id)->first());
                }
            }
        } finally {
            Activity::flushEventListeners();
        }
        $this->assertSame($hash, SalesReturnFixture::fingerprint());
        $log = Activity::where('log_name', 'sales.returns')->sole();
        $this->assertSame($id, $log->subject_id);
        $this->assertSame((string) $this->manager->id, (string) $log->causer_id);
        $this->assertStringNotContainsString('Barang diperiksa', $log->properties->toJson());
    }

    public function test_demo_is_relational_idempotent_and_preserves_edited_trashed_drafts(): void
    {
        $hash = SalesReturnFixture::fingerprint();
        $this->seed(ReturnsDemoSeeder::class);
        $draft = DB::table('sales_returns')->sole();
        $this->putJson('/sales/returns/'.$draft->id, array_replace($this->input, ['notes' => 'Diubah user', 'version' => 1]))->assertOk();
        $this->deleteJson('/sales/returns/'.$draft->id, ['version' => 2])->assertOk();
        $this->seed(ReturnsDemoSeeder::class);
        $this->assertDatabaseCount('sales_returns', 1);
        $this->assertDatabaseHas('sales_returns', ['id' => $draft->id, 'version' => 3, 'notes' => 'Diubah user']);
        $this->assertSame($hash, SalesReturnFixture::fingerprint());
    }

    public function test_demo_refuses_production_and_no_source_skips_without_posting(): void
    {
        DB::table('sales_invoices')->update(['customer_code' => 'REAL-CUSTOMER']);
        $hash = SalesReturnFixture::fingerprint();
        $this->seed(ReturnsDemoSeeder::class);
        $this->assertDatabaseCount('sales_returns', 0);
        $this->assertSame($hash, SalesReturnFixture::fingerprint());
        $this->app->detectEnvironment(fn () => 'production');
        $this->expectException(\LogicException::class);
        app(ReturnsDemoSeeder::class)->run();
    }

    public function test_multi_unit_return_uses_original_line_ratio_and_rejects_other_invoice_lines(): void
    {
        $box = SalesReturnFixture::seed('BOX', '1');
        $line = $box['lines'][0];
        $input = ['invoice_id' => $box['id'], 'lines' => [['invoice_line_id' => $line['id'], 'quantity' => '0.125']]];
        $this->postJson('/sales/returns', $input)->assertCreated();
        $row = $this->getJson('/sales/returns/data')->assertOk()->json('data.0.lines.0');
        $this->assertSame('0.500000', $row['base_quantity']);
        $this->assertSame('4.000000', $row['snapshot']['conversion_ratio']);
        $this->assertSame($line['unit_price'], $row['snapshot']['unit_price']);
        $input['invoice_id'] = $this->invoice['id'];
        $this->postJson('/sales/returns', $input)->assertUnprocessable();
    }

    public function test_audit_restore_veto_and_model_save_veto_preserve_state(): void
    {
        $id = app(ManageReturnDraft::class)->save($this->input);
        app(ManageReturnDraft::class)->lifecycle($id, 1, false);
        Activity::saving(fn ($log) => $log->log_name === 'sales.returns' ? false : null);
        try {
            app(ManageReturnDraft::class)->lifecycle($id, 2, true);
            $this->fail('Audit restore wajib berhasil.');
        } catch (RuntimeException) {
            $this->assertNotNull(DB::table('sales_returns')->where('id', $id)->value('deleted_at'));
            $this->assertDatabaseHas('sales_returns', ['id' => $id, 'version' => 2]);
        } finally {
            Activity::flushEventListeners();
        }
        SalesReturn::saving(fn () => false);
        try {
            app(ManageReturnDraft::class)->save($this->input);
            $this->fail('Veto model wajib membatalkan save.');
        } catch (RuntimeException) {
            $this->assertDatabaseCount('sales_returns', 1);
        } finally {
            SalesReturn::flushEventListeners();
        }
    }

    public function test_noop_comparison_preserves_large_decimal_and_numeric_notes_exactly(): void
    {
        // Fixture batas presisi; bukan posting atau perubahan invoice oleh fitur retur.
        DB::table('sales_invoice_lines')->where('id', $this->input['lines'][0]['invoice_line_id'])->update([
            'quantity' => '99999999999999.999999', 'base_quantity' => '99999999999999.999999']);
        $this->input['lines'][0]['quantity'] = '99999999999998.000001';
        $this->input['notes'] = '01';
        $id = app(ManageReturnDraft::class)->save($this->input);
        $this->input['lines'][0]['quantity'] = '99999999999998.000002';
        $this->input['notes'] = '1';
        app(ManageReturnDraft::class)->save($this->input + ['version' => 1], $id);
        $row = $this->getJson('/sales/returns/data')->assertOk()->json('data.0');
        $this->assertSame(2, $row['version']);
        $this->assertSame('1', $row['notes']);
        $this->assertSame('99999999999998.000002', $row['lines'][0]['quantity']);
    }
}
