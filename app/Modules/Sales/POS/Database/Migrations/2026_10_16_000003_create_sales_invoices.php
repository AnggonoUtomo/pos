<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $decimal = static function (Blueprint $table, string $name, int $precision, int $scale): void {
            if (DB::getDriverName() === 'sqlite') {
                $table->string($name, $precision + 2);
            } else {
                $table->decimal($name, $precision, $scale);
            }
        };
        Schema::create('sales_invoices', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->string('number', 32)->unique();
            $table->unsignedBigInteger('actor_id');
            $table->ulid('idempotency_key');
            $table->char('request_hash', 64);
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignUlid('customer_level_id')->constrained('customer_levels')->restrictOnDelete();
            $table->string('warehouse_code', 32);
            $table->string('warehouse_name', 150);
            $table->string('customer_code', 32);
            $table->string('customer_name', 150);
            $table->string('customer_level_name', 150);
            foreach (['subtotal', 'discount_amount', 'tax_amount', 'total'] as $column) {
                $decimal($table, $column, 30, 2);
            }
            $decimal($table, 'total_cost', 30, 6);
            $table->ulid('stock_issue_id');
            $table->ulid('payment_receipt_id');
            $table->json('payments');
            $table->timestamp('posted_at');
            $table->unique(['actor_id', 'idempotency_key']);
        });
        Schema::create('sales_invoice_lines', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('invoice_id')->constrained('sales_invoices')->restrictOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignUlid('base_unit_id')->constrained('units')->restrictOnDelete();
            $table->string('sku', 64);
            $table->string('name', 150);
            $table->string('unit_name', 150);
            $table->string('base_unit_name', 150);
            foreach (['quantity', 'base_quantity', 'conversion_ratio'] as $column) {
                $decimal($table, $column, 20, 6);
            }
            foreach (['unit_price', 'subtotal', 'discount_amount', 'tax_amount'] as $column) {
                $decimal($table, $column, 30, 2);
            }
            $decimal($table, 'total_cost', 30, 6);
            $table->unique(['invoice_id', 'line_number']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_invoices') && DB::table('sales_invoices')->exists()) {
            throw new LogicException('Invoice posted tidak boleh dihapus lewat rollback migration.');
        }
        Schema::dropIfExists('sales_invoice_lines');
        Schema::dropIfExists('sales_invoices');
    }
};
