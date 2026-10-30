<?php

namespace Tests\Feature\Modules\Purchasing\Suppliers;

use App\Models\User;
use App\Modules\Purchasing\Suppliers\Application\Actions\ManageSupplier;
use App\Modules\Purchasing\Suppliers\Application\DTOs\SupplierData;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersAccessSeeder;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuppliersHttpTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/purchasing/suppliers';

    private function actor(array $permissions = []): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate('purchasing.suppliers.'.$permission, 'web'));
        }
        $this->actingAs($user);

        return $user;
    }

    public function test_permissions_and_super_system_do_not_bypass_validation(): void
    {
        $this->get(self::URL)->assertRedirect('/login');
        $this->postJson(self::URL, [])->assertUnauthorized();
        $this->actor();
        $this->getJson(self::URL.'/data')->assertForbidden();
        $this->postJson(self::URL, [])->assertForbidden();
        $this->actor(['view']);
        $this->getJson(self::URL.'/data')->assertOk();
        $this->postJson(self::URL, [])->assertForbidden();
        $this->actor(['manage']);
        $this->getJson(self::URL.'/data')->assertForbidden();
        $id = $this->postJson(self::URL, ['code' => 'S1', 'name' => 'Supplier'])->assertCreated()->json('id');
        $super = $this->actor();
        $super->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->getJson(self::URL.'/data')->assertOk();
        $this->putJson(self::URL.'/'.$id, ['code' => '', 'name' => ''])->assertUnprocessable();
        $this->deleteJson(self::URL.'/'.$id)->assertOk();
        $this->postJson(self::URL.'/'.$id.'/restore')->assertOk();
    }

    public function test_http_lifecycle_validation_and_whitelisted_input(): void
    {
        $actor = $this->actor(['view', 'manage']);
        $data = ['code' => ' sup-cctv ', 'name' => ' Supplier CCTV ', 'phone' => '', 'email' => '',
            'address' => 'Alamat demo', 'id' => (string) Str::ulid(), 'deleted_at' => now(), 'causer_id' => 'untrusted'];
        $id = $this->postJson(self::URL, $data)->assertCreated()->json('id');
        $this->assertNotSame($data['id'], $id);
        $this->assertDatabaseHas('suppliers', ['id' => $id, 'code' => 'SUP-CCTV', 'phone' => null, 'email' => null, 'deleted_at' => null]);
        $this->assertDatabaseHas('activity_log', ['subject_id' => $id, 'causer_id' => $actor->id]);
        $this->postJson(self::URL, ['code' => 'sup-cctv', 'name' => 'Duplicate'])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->putJson(self::URL.'/'.strtoupper($id), ['code' => 'SUP-CCTV', 'name' => 'Updated', 'is_active' => false])->assertOk();
        $this->deleteJson(self::URL.'/'.$id)->assertOk();
        $this->putJson(self::URL.'/'.$id, ['code' => 'SUP-CCTV', 'name' => 'Updated'])->assertNotFound();
        $this->postJson(self::URL, ['code' => 'SUP-CCTV', 'name' => 'Duplicate'])->assertUnprocessable();
        $this->postJson(self::URL.'/'.$id.'/restore')->assertOk();
        $this->assertDatabaseHas('suppliers', ['id' => $id, 'is_active' => false, 'deleted_at' => null]);
        $this->postJson(self::URL.'/'.$id.'/restore')->assertNotFound();
        $this->deleteJson(self::URL.'/invalid')->assertNotFound();
        $this->putJson(self::URL.'/'.strtolower((string) Str::ulid()), ['code' => 'MISSING', 'name' => 'Missing'])->assertNotFound();
        $this->postJson(self::URL, ['code' => str_repeat('x', 33), 'name' => str_repeat('x', 151),
            'phone' => str_repeat('x', 33), 'email' => 'invalid', 'address' => str_repeat('x', 501), 'is_active' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors(['code', 'name', 'phone', 'email', 'address', 'is_active']);
    }

    public function test_filters_literal_search_pagination_and_inertia_are_readonly(): void
    {
        $this->withoutVite();
        $this->actor(['view']);
        $action = app(ManageSupplier::class);
        for ($i = 0; $i < 27; $i++) {
            $action->save(new SupplierData(sprintf('S%02d', $i), 'Supplier '.$i));
        }
        $id = $action->save(new SupplierData('SPECIAL', 'CCTV 100%_!', isActive: false));
        $logs = DB::table('activity_log')->count();
        $this->getJson(self::URL.'/data?per_page=25')->assertJsonCount(25, 'data')->assertJsonPath('meta.total', 28);
        $this->getJson(self::URL.'/data?page=2&per_page=25')->assertJsonCount(3, 'data');
        $this->getJson(self::URL.'/data?search='.urlencode('%_!'))->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $id);
        $this->getJson(self::URL.'/data?status=inactive')->assertJsonPath('meta.total', 1);
        $this->getJson(self::URL.'/data?status=active')->assertJsonPath('meta.total', 27);
        $this->get(self::URL)->assertInertia(fn (Assert $page) => $page->component('purchasing/suppliers/index')->has('records.data', 10)->where('filters.status', 'all'));
        $this->assertSame($logs, DB::table('activity_log')->count());
        $action->delete($id);
        $this->getJson(self::URL.'/data?trashed=1')->assertJsonPath('meta.total', 1);
        $this->getJson(self::URL.'/data?status=invalid&page=0&trashed=invalid')->assertUnprocessable()->assertJsonValidationErrors(['status', 'page', 'trashed']);
        $this->getJson(self::URL.'/data?search='.str_repeat('a', 151))->assertUnprocessable();
    }

    public function test_catalog_and_demo_are_additive_idempotent_without_grants(): void
    {
        $this->seed(SuppliersAccessSeeder::class);
        $this->seed(SuppliersAccessSeeder::class);
        $this->assertSame(2, Permission::where('name', 'like', 'purchasing.suppliers.%')->count());
        $this->seed(SuppliersDemoSeeder::class);
        $this->assertDatabaseCount('suppliers', 3);
        $row = DB::table('suppliers')->where('code', 'DEMO-SUP-CCTV')->first();
        app(ManageSupplier::class)->save(new SupplierData($row->code, 'Nama edit pengguna', isActive: false), $row->id);
        app(ManageSupplier::class)->delete($row->id);
        $before = DB::table('suppliers')->orderBy('id')->get()->toJson();
        $logs = DB::table('activity_log')->count();
        $this->seed(SuppliersDemoSeeder::class);
        $this->assertSame($before, DB::table('suppliers')->orderBy('id')->get()->toJson());
        $this->assertSame($logs, DB::table('activity_log')->count());
        foreach (['users', 'model_has_roles', 'role_has_permissions', 'model_has_permissions', 'sales_invoices', 'stock_movements'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_demo_is_rejected_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->expectException(LogicException::class);
        app(SuppliersDemoSeeder::class)->run();
    }

    public function test_write_routes_require_csrf_outside_test_bypass(): void
    {
        $this->actor(['view', 'manage']);
        $id = app(ManageSupplier::class)->save(new SupplierData('CSRF', 'Supplier CSRF'));
        $this->app->detectEnvironment(fn () => 'local');
        $this->postJson(self::URL, ['code' => 'UNTRUSTED', 'name' => 'Missing CSRF'])->assertStatus(419);
        $this->putJson(self::URL.'/'.$id, ['code' => 'CSRF', 'name' => 'Changed'])->assertStatus(419);
        $this->deleteJson(self::URL.'/'.$id)->assertStatus(419);
        $this->postJson(self::URL.'/'.$id.'/restore')->assertStatus(419);
        $this->assertDatabaseCount('suppliers', 1);
    }
}
