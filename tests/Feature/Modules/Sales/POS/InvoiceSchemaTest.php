<?php

namespace Tests\Feature\Modules\Sales\POS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InvoiceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_schema_is_additive_and_empty_rollback_keeps_stock_and_payments(): void
    {
        $this->assertTrue(Schema::hasColumns('sales_invoices', ['id', 'actor_id', 'idempotency_key', 'request_hash', 'payments', 'total_cost']));
        $this->assertTrue(Schema::hasColumns('sales_invoice_lines', ['invoice_id', 'base_quantity', 'conversion_ratio', 'unit_price', 'total_cost']));
        foreach (['sales_invoices', 'sales_invoice_lines'] as $table) {
            $this->assertFalse(Schema::hasColumn($table, 'deleted_at'));
        }
        $migration = require app_path('Modules/Sales/POS/Database/Migrations/2026_10_16_000003_create_sales_invoices.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('sales_invoices'));
        $this->assertTrue(Schema::hasTable('stock_issues'));
        $this->assertTrue(Schema::hasTable('payment_receipts'));
        $migration->up();
        $this->assertTrue(Schema::hasTable('sales_invoice_lines'));
    }
}
