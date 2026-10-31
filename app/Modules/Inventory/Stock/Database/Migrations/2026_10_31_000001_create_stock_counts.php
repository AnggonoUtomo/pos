<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('number', 32)->unique();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('active_warehouse_id')->nullable()->unique()->constrained('warehouses')->restrictOnDelete();
            $table->string('warehouse_name');
            $table->string('status', 16)->index();
            $table->unsignedInteger('version')->default(0);
            $table->string('notes', 500);
            $table->string('reason', 500)->nullable();
            $table->char('post_hash', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('stock_count_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('count_id')->constrained('stock_counts')->restrictOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('base_unit_id')->constrained('units')->restrictOnDelete();
            $table->string('sku', 64);
            $table->string('item_name');
            $table->string('base_unit_name');
            foreach (['system_quantity', 'counted_quantity', 'unit_cost'] as $column) {
                if (Schema::getConnection()->getDriverName() === 'sqlite') {
                    $definition = $table->string($column, 21);
                } else {
                    $definition = $table->decimal($column, 20, 6);
                }
                if ($column !== 'system_quantity') {
                    $definition->nullable();
                }
            }
            $table->foreignUlid('adjustment_id')->nullable()->constrained('stock_adjustments')->restrictOnDelete();
            $table->unique(['count_id', 'item_id']);
        });
    }

    public function down(): void
    {
        if (DB::table('stock_counts')->exists()) {
            throw new LogicException('Sesi opname berhistori tidak boleh dihapus lewat rollback.');
        }
        Schema::dropIfExists('stock_count_lines');
        Schema::dropIfExists('stock_counts');
    }
};
