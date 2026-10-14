<?php

namespace Tests\Feature\Modules\Finance\Payments;

use App\Models\User;
use App\Modules\Finance\Payments\Application\Actions\ManagePaymentAccount;
use App\Modules\Finance\Payments\Application\Actions\ManagePaymentMethod;
use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;
use App\Modules\Finance\Payments\Application\DTOs\PaymentAccountData;
use App\Modules\Finance\Payments\Application\DTOs\PaymentMethodData;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsAccessSeeder;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentsFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function cash(): string
    {
        return app(ManagePaymentAccount::class)->save(new PaymentAccountData(' CASH ', ' Kas Toko ', 'cash'));
    }

    public function test_masters_are_normalized_ulid_audited_and_noop_is_quiet(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->cash();
        $method = app(ManagePaymentMethod::class)->save(new PaymentMethodData(' TUNAI ', ' Tunai ', 'cash', $account));
        $this->assertTrue(Str::isUlid($account) && Str::isUlid($method));
        $this->assertDatabaseHas('payment_accounts', ['id' => $account, 'code' => 'CASH', 'name' => 'Kas Toko']);
        $this->assertDatabaseCount('activity_log', 2);
        $this->assertSame(2, DB::table('activity_log')->where('causer_id', $user->id)->count());
        app(ManagePaymentAccount::class)->save(new PaymentAccountData('CASH', 'Kas Toko', 'cash'), $account);
        app(ManagePaymentMethod::class)->save(new PaymentMethodData('TUNAI', 'Tunai', 'cash', $account), $method);
        $this->assertDatabaseCount('activity_log', 2);
    }

    public function test_compatibility_and_active_account_are_required_without_partial_rows(): void
    {
        $account = $this->cash();
        foreach (['debit', 'transfer', 'unknown'] as $type) {
            try {
                app(ManagePaymentMethod::class)->save(new PaymentMethodData('FAIL', 'Gagal', $type, $account));
                $this->fail('Invalid account/type must fail.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('payment_methods', 0);
                $this->assertDatabaseCount('activity_log', 1);
            }
        }
        DB::table('payment_accounts')->where('id', $account)->update(['is_active' => false]);
        $this->expectException(ValidationException::class);
        app(ManagePaymentMethod::class)->save(new PaymentMethodData('FAIL', 'Gagal', 'cash', $account));
    }

    public function test_used_account_guard_includes_trashed_methods(): void
    {
        $account = $this->cash();
        $method = app(ManagePaymentMethod::class)->save(new PaymentMethodData('CASH', 'Tunai', 'cash', $account));
        app(ManagePaymentMethod::class)->delete($method);
        $before = DB::table('activity_log')->count();
        foreach (['delete', 'deactivate', 'type'] as $operation) {
            try {
                if ($operation === 'delete') {
                    app(ManagePaymentAccount::class)->delete($account);
                } else {
                    app(ManagePaymentAccount::class)->save(new PaymentAccountData('CASH', 'Kas Toko', $operation === 'type' ? 'bank' : 'cash', isActive: $operation !== 'deactivate'), $account);
                }
                $this->fail('Referenced account must remain valid.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('payments', $exception->errors());
            }
        }
        $this->assertSame($before, DB::table('activity_log')->count());
        app(ManagePaymentAccount::class)->save(new PaymentAccountData('CASH', 'Kas Ganti Nama', 'cash'), $account);
        $this->assertDatabaseHas('payment_accounts', ['id' => $account, 'is_active' => true, 'type' => 'cash', 'deleted_at' => null]);
    }

    public function test_bank_metadata_is_not_copied_to_audit_but_changes_are_recorded(): void
    {
        $id = app(ManagePaymentAccount::class)->save(new PaymentAccountData('BANK', 'Bank Operasional', 'bank', 'Bank QA', '00012345', 'Pemilik QA'));
        $logs = DB::table('activity_log')->get()->toJson();
        foreach (['Bank QA', '00012345', 'Pemilik QA'] as $private) {
            $this->assertStringNotContainsString($private, $logs);
        }
        app(ManagePaymentAccount::class)->save(new PaymentAccountData('BANK', 'Bank Operasional', 'bank', 'Bank QA', '00054321', 'Pemilik QA'), $id);
        $this->assertDatabaseCount('activity_log', 2);
        $latest = json_decode(DB::table('activity_log')->orderByDesc('id')->value('properties'), true);
        $this->assertSame(['account_number'], $latest['changed_fields']);
        $this->assertStringNotContainsString('00054321', json_encode($latest));
    }

    public function test_restore_requires_current_valid_account_and_keeps_identifiers(): void
    {
        $account = $this->cash();
        $method = app(ManagePaymentMethod::class)->save(new PaymentMethodData('CASH', 'Tunai', 'cash', $account));
        app(ManagePaymentMethod::class)->delete($method);
        DB::table('payment_accounts')->where('id', $account)->update(['is_active' => false]);
        try {
            app(ManagePaymentMethod::class)->restore($method);
            $this->fail('Inactive account must block restore.');
        } catch (ValidationException) {
            $this->assertSoftDeleted('payment_methods', ['id' => $method]);
        }
        DB::table('payment_accounts')->where('id', $account)->update(['is_active' => true]);
        app(ManagePaymentMethod::class)->restore($method);
        $this->assertDatabaseHas('payment_methods', ['id' => $method, 'deleted_at' => null]);
    }

    public function test_persistence_failure_rolls_back_method_and_audit(): void
    {
        $account = $this->cash();
        $repository = \Mockery::mock(app(PaymentsRepository::class))->makePartial();
        $repository->shouldReceive('saveMethod')->once()->andReturnUsing(function ($data): string {
            PaymentMethod::create($data);
            throw new \RuntimeException('Simulated failure');
        });
        $this->app->instance(PaymentsRepository::class, $repository);
        try {
            app(ManagePaymentMethod::class)->save(new PaymentMethodData('CASH', 'Tunai', 'cash', $account));
            $this->fail('Must rollback.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('payment_methods', 0);
        $this->assertDatabaseCount('activity_log', 1);
    }

    public function test_demo_is_relational_idempotent_preserves_edits_and_never_grants_access(): void
    {
        $this->seed([PaymentsAccessSeeder::class, PaymentsDemoSeeder::class]);
        $this->assertDatabaseCount('payment_accounts', 2);
        $this->assertDatabaseCount('payment_methods', 3);
        $this->assertDatabaseCount('activity_log', 5);
        $method = PaymentMethod::where('code', 'DEBIT')->sole();
        $method->update(['name' => 'Metode User']);
        $method->delete();
        $snapshot = fn () => array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson(), ['payment_accounts', 'payment_methods', 'activity_log']);
        $before = $snapshot();
        $this->seed([PaymentsAccessSeeder::class, PaymentsDemoSeeder::class]);
        $this->assertSame($before, $snapshot());
        foreach (['users', 'roles', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->app['env'] = 'production';
        try {
            app(PaymentsDemoSeeder::class)->run();
            $this->fail('Production demo forbidden.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('local/testing', $exception->getMessage());
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_incompatible_existing_demo_account_rolls_back_new_batch(): void
    {
        app(ManagePaymentAccount::class)->save(new PaymentAccountData('BANK-DEMO', 'Existing', 'cash'));
        try {
            $this->seed(PaymentsDemoSeeder::class);
            $this->fail('Invalid prerequisite must rollback.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('payment_accounts', 1);
            $this->assertDatabaseCount('payment_methods', 0);
            $this->assertDatabaseCount('activity_log', 1);
        }
    }

    public function test_migration_can_roll_back_and_reapply(): void
    {
        $migration = require app_path('Modules/Finance/Payments/Database/Migrations/2026_10_15_000001_create_payment_masters.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('payment_accounts'));
        $migration->up();
        $this->assertTrue(Schema::hasTable('payment_methods'));
    }
}
