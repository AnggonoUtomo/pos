<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Payments\Application\Actions\ManageCashbook;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use Database\Seeders\OperationalLifecycleSeeder;
use Database\Seeders\Support\ReplaceOperationalTrainingOnce;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\MvpOperationalScenario;
use Tests\TestCase;

class OperationalLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super-system', 'web'));

        return $user;
    }

    public function test_complete_lifecycle_has_expected_stock_prices_drafts_and_is_idempotent(): void
    {
        $actor = $this->actor();
        $users = DB::table('users')->get()->toJson();
        $roles = DB::table('model_has_roles')->get()->toJson();
        $result = app(OperationalLifecycleSeeder::class)->run($actor->id);
        $this->assertDatabaseCount('items', 6);
        $this->assertDatabaseCount('item_prices', 36);
        $this->assertDatabaseCount('sales_invoices', 3);
        $this->assertDatabaseCount('purchase_orders', 2);
        $this->assertDatabaseCount('sales_returns', 2);
        $this->assertDatabaseCount('purchase_returns', 2);
        $this->assertDatabaseCount('stock_transfers', 2);
        $this->assertDatabaseCount('sales_return_settlements', 1);
        $this->assertDatabaseCount('purchase_return_settlements', 1);
        $camera = DB::table('items')->where('sku', 'LAT-KAMERA')->value('id');
        foreach (['LAT-UTAMA' => '3.000000', 'LAT-CADANGAN' => '2.000000'] as $code => $quantity) {
            $warehouse = DB::table('warehouses')->where('code', $code)->value('id');
            $this->assertEquals((float) $quantity, (float) DB::table('stock_balances')->where('item_id', $camera)->where('warehouse_id', $warehouse)->value('quantity'));
        }
        foreach (['purchase' => 'purchase_orders', 'sales_return' => 'sales_returns', 'purchase_return' => 'purchase_returns', 'transfer' => 'stock_transfers'] as $key => $table) {
            $this->assertSame('draft', DB::table($table)->where('id', $result['drafts'][$key])->value('status'));
        }
        $hash = ReplaceOperationalTrainingOnce::fingerprint();
        $auditCount = Activity::count();
        $this->assertSame($result, app(OperationalLifecycleSeeder::class)->run($actor->id));
        $this->assertSame($hash, ReplaceOperationalTrainingOnce::fingerprint());
        $this->assertSame($auditCount, Activity::count());
        $this->assertSame($users, DB::table('users')->get()->toJson());
        $this->assertSame($roles, DB::table('model_has_roles')->get()->toJson());
    }

    public function test_normal_seeder_refuses_nonempty_database_without_deleting_data(): void
    {
        $actor = $this->actor();
        DB::table('units')->insert(['id' => strtolower((string) Str::ulid()), 'code' => 'OLD', 'name' => 'Existing', 'symbol' => 'old', 'is_active' => true]);
        $this->expectException(LogicException::class);
        try {
            app(OperationalLifecycleSeeder::class)->run($actor->id);
        } finally {
            $this->assertDatabaseCount('units', 1);
            $this->assertDatabaseCount('items', 0);
        }
    }

    public function test_production_requires_explicit_cli_authorization(): void
    {
        $actor = $this->actor();
        $this->app->instance('env', 'production');
        $this->expectException(LogicException::class);
        app(OperationalLifecycleSeeder::class)->run($actor->id);
    }

    public function test_audit_failure_rolls_back_complete_dataset(): void
    {
        $actor = $this->actor();
        Activity::saving(function (Activity $activity): void {
            if ($activity->event === 'training.lifecycle.seeded') {
                throw new RuntimeException('Training audit unavailable');
            }
        });
        try {
            app(OperationalLifecycleSeeder::class)->run($actor->id);
            $this->fail('Expected training audit failure');
        } catch (RuntimeException $error) {
            $this->assertSame('Training audit unavailable', $error->getMessage());
            foreach (OperationalLifecycleSeeder::BUSINESS_TABLES as $table) {
                $this->assertDatabaseCount($table, 0);
            }
        } finally {
            Activity::flushEventListeners();
        }
    }

    public function test_one_time_replacement_preserves_protected_rows_and_refuses_second_reset(): void
    {
        $actor = $this->actor();
        MvpOperationalScenario::run();
        DB::table('units')->insert(['id' => strtolower((string) Str::ulid()), 'code' => 'OLD', 'name' => 'Existing', 'symbol' => 'old', 'is_active' => true]);
        activity('testing')->event('old.audit')->log('Old audit must survive');
        $oldAudit = Activity::where('event', 'old.audit')->sole()->getAttributes();
        $users = DB::table('users')->get()->toJson();
        $replacement = app(ReplaceOperationalTrainingOnce::class);
        $replacement->run(ReplaceOperationalTrainingOnce::fingerprint(), $actor->id);
        $this->assertDatabaseMissing('units', ['code' => 'OLD']);
        $this->assertDatabaseCount('items', 6);
        $this->assertSame($users, DB::table('users')->get()->toJson());
        $this->assertSame($oldAudit, Activity::where('event', 'old.audit')->sole()->getAttributes());
        $newHash = ReplaceOperationalTrainingOnce::fingerprint();
        $this->expectException(LogicException::class);
        try {
            $replacement->run($newHash, $actor->id);
        } finally {
            $this->assertSame($newHash, ReplaceOperationalTrainingOnce::fingerprint());
        }
    }

    public function test_wrong_fingerprint_refuses_deletion(): void
    {
        $actor = $this->actor();
        $hash = ReplaceOperationalTrainingOnce::fingerprint();
        $this->expectException(LogicException::class);
        try {
            app(ReplaceOperationalTrainingOnce::class)->run(str_repeat('0', 64), $actor->id);
        } finally {
            $this->assertSame($hash, ReplaceOperationalTrainingOnce::fingerprint());
        }
    }

    public function test_cashbook_data_blocks_legacy_replacement_without_being_deleted(): void
    {
        $actor = $this->actor();
        app(PaymentsDemoSeeder::class)->run();
        $account = DB::table('payment_accounts')->value('id');
        app(ManageCashbook::class)->open([
            'account_id' => $account, 'starts_on' => now('Asia/Jakarta')->toDateString(), 'amount' => '100',
        ], $actor->id);
        $hash = ReplaceOperationalTrainingOnce::fingerprint();
        $this->expectException(LogicException::class);
        try {
            app(ReplaceOperationalTrainingOnce::class)->run($hash, $actor->id);
        } finally {
            $this->assertDatabaseCount('cashbook_openings', 1);
            $this->assertSame($hash, ReplaceOperationalTrainingOnce::fingerprint());
        }
    }

    public function test_failed_replacement_audit_restores_old_posted_transactions_and_settings(): void
    {
        $actor = $this->actor();
        MvpOperationalScenario::run();
        $hash = ReplaceOperationalTrainingOnce::fingerprint();
        $audits = Activity::count();
        Activity::saving(function (Activity $activity): void {
            if ($activity->event === 'training.lifecycle.replaced_once') {
                throw new RuntimeException('Replacement audit unavailable');
            }
        });
        try {
            app(ReplaceOperationalTrainingOnce::class)->run($hash, $actor->id);
            $this->fail('Expected replacement failure');
        } catch (RuntimeException $error) {
            $this->assertSame('Replacement audit unavailable', $error->getMessage());
            $this->assertSame($hash, ReplaceOperationalTrainingOnce::fingerprint());
            $this->assertSame($audits, Activity::count());
            $this->assertNull(OperationalLifecycleSeeder::marker());
        } finally {
            Activity::flushEventListeners();
        }
    }

    public function test_unexpected_protected_reference_prevents_deletion(): void
    {
        $actor = $this->actor();
        Schema::create('protected_reference', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('unit_id')->constrained('units');
        });
        $hash = ReplaceOperationalTrainingOnce::fingerprint();
        $this->expectException(LogicException::class);
        try {
            app(ReplaceOperationalTrainingOnce::class)->run($hash, $actor->id);
        } finally {
            $this->assertSame($hash, ReplaceOperationalTrainingOnce::fingerprint());
            Schema::drop('protected_reference');
        }
    }

    public function test_replacement_only_introspects_the_current_database_schema(): void
    {
        $actor = $this->actor();
        $schema = Schema::getFacadeRoot();
        $scoped = \Mockery::mock($schema);
        $scoped->shouldReceive('getTables')->with($schema->getCurrentSchemaListing())->once()
            ->andReturn($schema->getTables($schema->getCurrentSchemaListing()));
        Schema::swap($scoped);
        try {
            app(ReplaceOperationalTrainingOnce::class)->run(ReplaceOperationalTrainingOnce::fingerprint(), $actor->id);
        } finally {
            Schema::swap($schema);
        }
    }
}
