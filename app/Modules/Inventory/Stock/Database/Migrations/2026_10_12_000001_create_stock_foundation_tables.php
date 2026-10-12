<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite NUMERIC affinity loses decimal digits; disposable QA stores exact strings.
        $decimal = static function (Blueprint $table, string $name, int $precision): void {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->string($name, $precision + 1);
            } else {
                $table->decimal($name, $precision, 6);
            }
        };
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
        Schema::create('stock_opening_lines', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('opening_id')->constrained('stock_openings')->restrictOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignUlid('base_unit_id')->constrained('units')->restrictOnDelete();
            $table->string('sku', 64);
            $table->string('item_name', 150);
            $table->string('unit_name', 150);
            $table->string('base_unit_name', 150);
            foreach (['quantity', 'conversion_ratio', 'unit_cost', 'base_quantity'] as $name) {
                $decimal($table, $name, 20);
            }
            $decimal($table, 'total_value', 30);
            $table->unique(['opening_id', 'item_id']);
        });
        Schema::create('stock_balances', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('opening_line_id')->unique()->constrained('stock_opening_lines')->restrictOnDelete();
            $decimal($table, 'quantity', 20);
            $decimal($table, 'total_value', 30);
            $table->timestamps();
            $table->unique(['warehouse_id', 'item_id']);
        });
        Schema::create('stock_movements', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('opening_line_id')->unique()->constrained('stock_opening_lines')->restrictOnDelete();
            $table->string('type', 32);
            $decimal($table, 'quantity', 20);
            $decimal($table, 'total_value', 30);
            $table->timestamp('posted_at');
            $table->index(['warehouse_id', 'item_id', 'posted_at', 'id'], 'stock_movements_fifo_order');
        });
        Schema::create('fifo_layers', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('movement_id')->unique()->constrained('stock_movements')->restrictOnDelete();
            $decimal($table, 'original_quantity', 20);
            $decimal($table, 'remaining_quantity', 20);
            $decimal($table, 'original_value', 30);
            $decimal($table, 'remaining_value', 30);
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
