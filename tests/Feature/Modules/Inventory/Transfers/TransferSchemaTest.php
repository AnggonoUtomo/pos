<?php

namespace Tests\Feature\Modules\Inventory\Transfers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransferSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_discovers_module_migration_and_empty_down_up(): void
    {
        $this->assertTrue(Schema::hasTable('stock_transfers'));
        $this->assertTrue(Schema::hasTable('stock_transfer_lines'));
        $this->assertTrue(Schema::hasColumn('stock_transfers', 'posted_at'));
        $this->assertFalse(Schema::hasColumn('stock_transfer_lines', 'unit_cost'));
        $migration = require app_path('Modules/Inventory/Transfers/Database/Migrations/2026_10_23_000003_create_stock_transfer_drafts.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('stock_transfers'));
        $migration->up();
        $this->assertTrue(Schema::hasTable('stock_transfer_lines'));
    }
}
