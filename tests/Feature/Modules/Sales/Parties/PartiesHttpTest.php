<?php

namespace Tests\Feature\Modules\Sales\Parties;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartiesHttpTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->create();
        foreach (['sales.parties.view', 'sales.parties.manage'] as $name) {
            $this->manager->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $this->actingAs($this->manager);
    }

    private function level(string $code = 'ECERAN'): string
    {
        return $this->postJson('/sales/customer-levels', ['code' => $code, 'name' => 'Eceran'])->assertCreated()->json('id');
    }

    private function customer(string $level, string $code = 'C001'): array
    {
        return ['code' => $code, 'name' => 'Installer CCTV', 'customer_level_id' => $level,
            'phone' => 'DEMO-CONTACT', 'email' => 'installer@example.test', 'address' => 'Alamat demo'];
    }

    public function test_crud_restore_audit_and_privacy(): void
    {
        $level = $this->level();
        $data = $this->customer($level, ' c001 ') + ['unexpected' => 'not persisted'];
        $id = $this->postJson('/sales/customers', $data)->assertCreated()->json('id');
        $this->assertTrue(Str::isUlid($id));
        $this->assertDatabaseHas('customers', ['id' => $id, 'code' => 'C001', 'is_active' => true]);
        $data['code'] = 'C001';
        $data['name'] = 'Installer Updated';
        $data['is_active'] = false;
        $this->putJson('/sales/customers/'.$id, $data)->assertOk();
        $count = Activity::count();
        $this->putJson('/sales/customers/'.$id, $data)->assertOk();
        $this->assertSame($count, Activity::count());
        $this->deleteJson('/sales/customers/'.$id)->assertOk();
        $this->assertSoftDeleted('customers', ['id' => $id]);
        $this->postJson('/sales/customers/'.$id.'/restore')->assertOk();
        $this->assertDatabaseHas('customers', ['id' => $id, 'deleted_at' => null, 'is_active' => false]);
        $logs = Activity::where('log_name', 'sales.customers')->get();
        $this->assertCount(4, $logs);
        $this->assertTrue($logs->every(fn ($log) => $log->causer->is($this->manager) && $log->subject !== null && Str::isUlid($log->subject_id)));
        $persisted = DB::table('activity_log')->where('log_name', 'sales.customers')->get()->toJson();
        $this->assertStringNotContainsString('installer@example.test', $persisted);
        $this->assertStringNotContainsString('DEMO-CONTACT', $persisted);
        $this->assertStringNotContainsString('Alamat demo', $persisted);
    }

    public function test_used_level_guard_including_trashed_customer_and_super_system(): void
    {
        $level = $this->level();
        $customer = $this->postJson('/sales/customers', $this->customer($level))->assertCreated()->json('id');
        $this->manager->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->deleteJson('/sales/customers/'.$customer)->assertOk();
        $count = Activity::count();
        $this->deleteJson('/sales/customer-levels/'.$level)->assertUnprocessable()->assertJsonValidationErrors('parties');
        $this->putJson('/sales/customer-levels/'.$level, ['code' => 'ECERAN', 'name' => 'Eceran', 'is_active' => false])
            ->assertUnprocessable()->assertJsonValidationErrors('parties');
        $this->assertSame($count, Activity::count());
        $this->assertDatabaseHas('customer_levels', ['id' => $level, 'is_active' => true, 'deleted_at' => null]);
        $this->putJson('/sales/customer-levels/'.$level, ['code' => 'RITEL', 'name' => 'Ritel', 'is_active' => true])->assertOk();
    }

    public function test_invalid_level_rejected_for_create_edit_restore_and_no_partial_audit(): void
    {
        $level = $this->level();
        $id = $this->postJson('/sales/customers', $this->customer($level))->assertCreated()->json('id');
        $this->deleteJson('/sales/customers/'.$id)->assertOk();
        DB::table('customer_levels')->where('id', $level)->update(['is_active' => false]);
        $count = Activity::count();
        $this->postJson('/sales/customers', $this->customer($level, 'C002'))->assertUnprocessable()->assertJsonValidationErrors('customer_level_id');
        $this->postJson('/sales/customers/'.$id.'/restore')->assertUnprocessable()->assertJsonValidationErrors('customer_level_id');
        $this->assertSoftDeleted('customers', ['id' => $id]);
        $this->assertSame($count, Activity::count());
        DB::table('customer_levels')->where('id', $level)->update(['is_active' => true]);
        $this->postJson('/sales/customers/'.$id.'/restore')->assertOk();
        $inactive = $this->level('OTHER');
        $this->putJson('/sales/customer-levels/'.$inactive, ['code' => 'OTHER', 'name' => 'Lain', 'is_active' => false])->assertOk();
        $this->putJson('/sales/customers/'.$id, $this->customer($inactive))->assertUnprocessable()->assertJsonValidationErrors('customer_level_id');
        $this->assertDatabaseHas('customers', ['id' => $id, 'customer_level_id' => $level]);
    }

    public function test_unused_level_lifecycle_reserves_code_and_validation_filters(): void
    {
        $level = $this->level();
        $this->deleteJson('/sales/customer-levels/'.$level)->assertOk();
        $this->postJson('/sales/customer-levels', ['code' => ' eceran ', 'name' => 'Duplicate'])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->postJson('/sales/customer-levels/'.$level.'/restore')->assertOk();
        $this->putJson('/sales/customer-levels/'.$level, ['code' => 'ECERAN', 'name' => 'Ritel', 'is_active' => false])->assertOk();
        $this->getJson('/sales/customer-levels/data?search=Ritel&status=inactive')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/sales/customers/data?page=0')->assertUnprocessable();
        $this->getJson('/sales/customers/data?status=bad')->assertUnprocessable();
        $this->getJson('/sales/customers/data?customer_level_id=bad')->assertUnprocessable();
        $this->postJson('/sales/customers', [])->assertUnprocessable()->assertJsonValidationErrors(['code', 'name', 'customer_level_id']);
        $this->putJson('/sales/customer-levels/not-ulid', [])->assertNotFound();
        $this->deleteJson('/sales/customers/'.strtolower(Str::ulid()))->assertNotFound();
    }

    public function test_customer_unique_optional_contact_level_filter_and_trashed_listing(): void
    {
        $one = $this->level();
        $two = $this->level('GROSIR');
        $id = $this->postJson('/sales/customers', ['code' => 'C001', 'name' => 'Umum', 'customer_level_id' => strtoupper($one)])->assertCreated()->json('id');
        $this->postJson('/sales/customers', $this->customer($two, 'C002'))->assertCreated();
        $this->getJson('/sales/customers/data?customer_level_id='.$two)->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.customer_level_name', 'Eceran');
        $this->deleteJson('/sales/customers/'.$id)->assertOk();
        $this->postJson('/sales/customers', $this->customer($two))->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->getJson('/sales/customers/data?trashed=1')->assertOk()->assertJsonPath('data.0.id', $id)->assertJsonPath('meta.total', 1);
        $this->postJson('/sales/customers', $this->customer($two, 'C003') + ['is_active' => 'bad'])->assertUnprocessable();
        $invalid = $this->customer($two, 'C004');
        $invalid['email'] = 'not-email';
        $this->postJson('/sales/customers', $invalid)->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_permission_backend_and_guest(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('sales.parties.view');
        $this->actingAs($viewer)->getJson('/sales/customers/data')->assertOk();
        $this->postJson('/sales/customer-levels', [])->assertForbidden();
        $this->postJson('/sales/customers', [])->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson('/sales/customers/data')->assertForbidden();
        $this->app['auth']->forgetGuards();
        auth()->logout();
        $this->getJson('/sales/customer-levels/data')->assertUnauthorized();
    }

    public function test_contact_only_edit_is_audited_without_contact_values_and_noop_is_not_logged(): void
    {
        $data = $this->customer($this->level());
        $id = $this->postJson('/sales/customers', $data)->assertCreated()->json('id');
        $count = Activity::count();
        $data['email'] = 'updated@example.test';
        $this->putJson('/sales/customers/'.$id, $data)->assertOk();
        $this->assertSame($count + 1, Activity::count());
        $log = Activity::latest('id')->first();
        $this->assertSame('updated', $log->event);
        $this->assertSame(['email'], $log->properties['changed_fields']);
        $this->assertTrue($log->causer->is($this->manager));
        $persisted = DB::table('activity_log')->where('id', $log->id)->first();
        $this->assertStringNotContainsString('updated@example.test', json_encode($persisted));
        $this->putJson('/sales/customers/'.$id, $data)->assertOk();
        $this->assertSame($count + 1, Activity::count());
    }
}
