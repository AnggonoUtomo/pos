<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_openings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('number', 32)->unique();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('warehouse_code', 32);
            $table->string('warehouse_name', 150);
            $table->string('status', 16)->default('draft')->index();
            $table->string('notes', 500)->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('stock_opening_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('opening_id')->constrained('stock_openings')->restrictOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignUlid('base_unit_id')->constrained('units')->restrictOnDelete();
            $table->string('sku', 64);
            $table->string('item_name', 150);
            $table->string('unit_name', 150);
            $table->string('base_unit_name', 150);
            $table->decimal('quantity', 20, 6);
            $table->decimal('conversion_ratio', 20, 6);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('base_quantity', 20, 6);
            $table->decimal('total_value', 30, 6);
            $table->unique(['opening_id', 'item_id']);
        });
        Schema::create('stock_balances', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('opening_line_id')->unique()->constrained('stock_opening_lines')->restrictOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->decimal('total_value', 30, 6);
            $table->timestamps();
            $table->unique(['warehouse_id', 'item_id']);
        });
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('opening_line_id')->unique()->constrained('stock_opening_lines')->restrictOnDelete();
            $table->string('type', 32);
            $table->decimal('quantity', 20, 6);
            $table->decimal('total_value', 30, 6);
            $table->timestamp('posted_at');
            $table->index(['warehouse_id', 'item_id', 'posted_at', 'id'], 'stock_movements_fifo_order');
        });
        Schema::create('fifo_layers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('movement_id')->unique()->constrained('stock_movements')->restrictOnDelete();
            $table->decimal('original_quantity', 20, 6);
            $table->decimal('remaining_quantity', 20, 6);
            $table->decimal('original_value', 30, 6);
            $table->decimal('remaining_value', 30, 6);
            $table->timestamp('received_at');
            $table->index(['warehouse_id', 'item_id', 'received_at', 'id'], 'fifo_layers_order');
        });
    }

    public function down(): void
    {
        foreach (['fifo_layers', 'stock_movements', 'stock_balances', 'stock_opening_lines', 'stock_openings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
