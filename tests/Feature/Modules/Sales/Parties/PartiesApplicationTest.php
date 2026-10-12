<?php

namespace Tests\Feature\Modules\Sales\Parties;

use App\Modules\Sales\Parties\Application\Actions\ManageCustomer;
use App\Modules\Sales\Parties\Application\Actions\ManageCustomerLevel;
use App\Modules\Sales\Parties\Application\DTOs\CustomerData;
use App\Modules\Sales\Parties\Application\DTOs\CustomerLevelData;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class PartiesApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_application_normalizes_and_enforces_used_level_guard(): void
    {
        $level = app(ManageCustomerLevel::class)->save(new CustomerLevelData(' retail ', ' Retail '));
        $id = app(ManageCustomer::class)->save(new CustomerData(' c001 ', ' CCTV ', strtoupper($level), ' ', ' '));
        $this->assertDatabaseHas('customers', ['id' => $id, 'code' => 'C001', 'name' => 'CCTV', 'phone' => null, 'email' => null]);
        $this->expectException(InvalidArgumentException::class);
        app(ManageCustomerLevel::class)->delete($level);
    }

    public function test_direct_application_rejects_invalid_input(): void
    {
        $this->expectException(ValidationException::class);
        app(ManageCustomer::class)->save(new CustomerData(' ', 'Name', 'not-ulid'));
    }

    public function test_failure_after_customer_and_audit_creation_rolls_back_both(): void
    {
        $level = app(ManageCustomerLevel::class)->save(new CustomerLevelData('ECERAN', 'Eceran'));
        $count = DB::table('activity_log')->count();
        Event::listen('eloquent.created: '.Customer::class, function (): void {
            throw new RuntimeException('QA rollback');
        });
        try {
            app(ManageCustomer::class)->save(new CustomerData('C001', 'CCTV', $level));
            $this->fail('Persistence failure must throw.');
        } catch (RuntimeException $exception) {
            $this->assertSame('QA rollback', $exception->getMessage());
        }
        $this->assertDatabaseCount('customers', 0);
        $this->assertSame($count, DB::table('activity_log')->count());
    }

    public function test_module_migration_can_roll_back_and_reapply(): void
    {
        $migration = require app_path('Modules/Sales/Parties/Database/Migrations/2026_10_12_000002_create_customer_masters.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('customers'));
        $this->assertFalse(Schema::hasTable('customer_levels'));
        $migration->up();
        $this->assertTrue(Schema::hasColumns('customers', ['id', 'customer_level_id', 'deleted_at']));
    }
}
