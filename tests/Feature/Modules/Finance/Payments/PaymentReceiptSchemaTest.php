<?php

namespace Tests\Feature\Modules\Finance\Payments;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentReceiptSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_schema_is_additive_and_can_roll_back_when_empty(): void
    {
        $this->assertTrue(Schema::hasColumns('payment_receipts', ['id', 'source_type', 'source_id', 'request_hash', 'total', 'tendered', 'change']));
        $this->assertTrue(Schema::hasColumns('payment_records', ['receipt_id', 'method_id', 'account_id', 'method_name', 'account_name', 'tendered_amount', 'applied_amount', 'change_amount']));
        $this->assertFalse(Schema::hasColumn('payment_records', 'deleted_at'));
        $this->assertFalse(Schema::hasColumn('payment_receipts', 'deleted_at'));
        $migration = require app_path('Modules/Finance/Payments/Database/Migrations/2026_10_16_000002_create_payment_receipts.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('payment_receipts'));
        $this->assertTrue(Schema::hasTable('payment_methods'));
        $migration->up();
        $this->assertTrue(Schema::hasTable('payment_records'));
    }
}
