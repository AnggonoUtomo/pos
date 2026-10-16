<?php

namespace Tests\Feature\Modules\Purchasing\Suppliers;

use App\Models\User;
use App\Modules\Purchasing\Suppliers\Application\Actions\ManageSupplier;
use App\Modules\Purchasing\Suppliers\Application\DTOs\SupplierData;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class SuppliersApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function save(string $code = ' sup-cctv ', ?string $id = null, bool $active = true): string
    {
        return app(ManageSupplier::class)->save(new SupplierData($code, ' Distributor CCTV ', ' 0800-private ', 'private@example.test', 'Alamat privat', $active), $id);
    }

    public function test_ulid_normalization_noop_and_private_audit(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $id = $this->save();
        $this->assertTrue(Str::isUlid($id));
        $this->assertDatabaseHas('suppliers', ['id' => $id, 'code' => 'SUP-CCTV', 'name' => 'Distributor CCTV', 'phone' => '0800-private']);
        $log = Activity::where('log_name', 'purchasing.suppliers')->sole();
        $this->assertSame($id, $log->subject_id);
        $this->assertSame($actor->id, $log->causer_id);
        $this->assertSame('created', $log->event);
        $this->save(id: $id);
        $this->assertSame(1, Activity::count());
        app(ManageSupplier::class)->save(new SupplierData('SUP-CCTV', 'Distributor CCTV', 'changed-private', null, null), $id);
        $log = Activity::latest('id')->first();
        $this->assertContains('phone', $log->properties['changed_fields']);
        foreach (['0800-private', 'changed-private', 'private@example.test', 'Alamat privat'] as $private) {
            $this->assertStringNotContainsString($private, Activity::all()->toJson());
        }
    }

    public function test_soft_delete_restore_retains_inactive_status_and_reserved_code(): void
    {
        $id = $this->save(active: false);
        $action = app(ManageSupplier::class);
        $action->delete($id);
        $this->assertSoftDeleted('suppliers', ['id' => $id]);
        $action->restore($id);
        $this->assertDatabaseHas('suppliers', ['id' => $id, 'is_active' => false, 'deleted_at' => null]);
        $this->assertSame(['created', 'deleted', 'restored'], Activity::orderBy('id')->pluck('event')->all());
        $action->delete($id);
        $this->expectException(ValidationException::class);
        $this->save();
    }

    public function test_invalid_application_input_is_rejected_without_mutation(): void
    {
        try {
            app(ManageSupplier::class)->save(new SupplierData('', '', email: 'not-email'));
            $this->fail('Input invalid diterima.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('code', $error->errors());
            $this->assertArrayHasKey('name', $error->errors());
            $this->assertArrayHasKey('email', $error->errors());
        }
        $this->assertDatabaseCount('suppliers', 0);
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_create_rolls_back_when_audit_is_vetoed(): void
    {
        Activity::creating(fn () => false);
        try {
            $this->save();
            $this->fail('Audit veto tidak boleh diabaikan.');
        } catch (RuntimeException) {
            $this->assertDatabaseCount('suppliers', 0);
            $this->assertDatabaseCount('activity_log', 0);
        } finally {
            Activity::flushEventListeners();
        }
    }

    public function test_all_existing_mutations_roll_back_on_audit_failure(): void
    {
        $id = $this->save();
        $action = app(ManageSupplier::class);
        foreach (['update', 'delete', 'restore'] as $operation) {
            if ($operation === 'restore') {
                $action->delete($id);
            }
            $before = DB::table('suppliers')->where('id', $id)->first();
            $logs = Activity::count();
            Activity::creating(fn () => throw new RuntimeException('Audit gagal.'));
            try {
                match ($operation) {
                    'update' => $this->save('CHANGED', $id),
                    'delete' => $action->delete($id),
                    'restore' => $action->restore($id),
                };
                $this->fail('Mutation harus rollback.');
            } catch (RuntimeException) {
                $this->assertEquals($before, DB::table('suppliers')->where('id', $id)->first());
                $this->assertSame($logs, Activity::count());
            } finally {
                Activity::flushEventListeners();
            }
        }
    }

    public function test_model_save_veto_does_not_create_audit_or_false_success(): void
    {
        Supplier::creating(fn () => false);
        try {
            $this->save();
            $this->fail('Persistence veto harus ditolak.');
        } catch (RuntimeException) {
            $this->assertDatabaseCount('suppliers', 0);
            $this->assertDatabaseCount('activity_log', 0);
        } finally {
            Supplier::flushEventListeners();
        }
    }

    public function test_migration_can_roll_back_and_reapply_on_disposable_database(): void
    {
        $migration = require app_path('Modules/Purchasing/Suppliers/Database/Migrations/2026_10_17_000001_create_suppliers_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('suppliers'));
        $migration->up();
        $this->assertTrue(Schema::hasColumns('suppliers', ['id', 'code', 'deleted_at', 'is_active']));
        $this->save();
        $this->assertDatabaseCount('suppliers', 1);
    }
}
