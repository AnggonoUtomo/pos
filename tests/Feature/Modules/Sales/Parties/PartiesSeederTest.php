<?php

namespace Tests\Feature\Modules\Sales\Parties;

use App\Modules\Sales\Parties\Database\Seeders\PartiesAccessSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\Customer;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\CustomerLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class PartiesSeederTest extends TestCase
{
    use RefreshDatabase;

    private function snapshot(): array
    {
        return array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson(), ['customer_levels', 'customers', 'activity_log']);
    }

    public function test_relational_demo_repeat_preserves_user_edits_deleted_rows_and_access(): void
    {
        $this->seed([PartiesAccessSeeder::class, PartiesDemoSeeder::class]);
        $this->assertDatabaseCount('customer_levels', 3);
        $this->assertDatabaseCount('customers', 3);
        $this->assertDatabaseCount('activity_log', 6);
        $this->assertSame(['sales.parties.manage', 'sales.parties.view'], DB::table('permissions')->orderBy('name')->pluck('name')->all());
        Customer::where('code', 'DEMO-INSTALLER')->sole()->update(['name' => 'Installer User', 'is_active' => false]);
        Customer::where('code', 'DEMO-GROSIR')->sole()->delete();
        CustomerLevel::where('code', 'INSTALLER')->sole()->update(['name' => 'Level User', 'is_active' => false]);
        CustomerLevel::where('code', 'GROSIR')->sole()->delete();
        $before = $this->snapshot();
        $this->seed([PartiesAccessSeeder::class, PartiesDemoSeeder::class]);
        $this->assertSame($before, $this->snapshot());
        foreach (['users', 'roles', 'role_has_permissions', 'model_has_roles', 'model_has_permissions'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_inactive_prerequisite_rolls_back_demo_customers_and_audit(): void
    {
        CustomerLevel::create(['code' => 'INSTALLER', 'name' => 'Installer', 'is_active' => false]);
        $before = $this->snapshot();
        try {
            $this->seed(PartiesDemoSeeder::class);
            $this->fail('Inactive demo level must fail.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('INSTALLER', $exception->getMessage());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function test_demo_refuses_production_before_writes(): void
    {
        $this->app['env'] = 'production';
        try {
            app(PartiesDemoSeeder::class)->run();
            $this->fail('Production demo must fail.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('local/testing', $exception->getMessage());
        } finally {
            $this->app['env'] = 'testing';
        }
        $this->assertDatabaseCount('customer_levels', 0);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('activity_log', 0);
    }
}
