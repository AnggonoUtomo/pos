<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $decimal = static function (Blueprint $table, string $name, int $precision): void {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->string($name, $precision + 1);
            } else {
                $table->decimal($name, $precision, 6);
            }
        };
        Schema::create('purchase_orders', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->string('number', 32)->unique();
            $table->string('demo_key', 64)->nullable()->unique();
            $table->foreignUlid('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            foreach (['supplier', 'warehouse'] as $prefix) {
                $table->string($prefix.'_code', 32);
                $table->string($prefix.'_name', 150);
            }
            $table->string('status', 16)->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->string('notes', 500)->nullable();
            $decimal($table, 'total_value', 30);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('purchase_order_lines', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignUlid('base_unit_id')->constrained('units')->restrictOnDelete();
            $table->string('sku', 64);
            foreach (['item_name', 'unit_name', 'base_unit_name'] as $field) {
                $table->string($field, 150);
            }
            foreach (['quantity', 'conversion_ratio', 'unit_cost', 'base_quantity'] as $field) {
                $decimal($table, $field, 20);
            }
            $decimal($table, 'total_value', 30);
            $table->unique(['purchase_order_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
