<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_tables_and_immutable_ledger_shape(): void
    {
        foreach (['stock_openings', 'stock_opening_lines', 'stock_balances', 'stock_movements', 'fifo_layers'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
            $this->assertTrue(Schema::hasColumn($table, 'id'));
            $this->assertSame($table === 'stock_openings', Schema::hasColumn($table, 'deleted_at'));
        }
    }

    public function test_migration_down_up_is_reversible_on_disposable_database(): void
    {
        $issues = require app_path('Modules/Inventory/Stock/Database/Migrations/2026_10_16_000001_add_stock_issues.php');
        $issues->down();
        $migration = require app_path('Modules/Inventory/Stock/Database/Migrations/2026_10_12_000001_create_stock_foundation_tables.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('stock_openings'));
        $this->assertTrue(Schema::hasTable('items'));
        $migration->up();
        $issues->up();
        $this->assertTrue(Schema::hasTable('fifo_layers'));
    }
}
