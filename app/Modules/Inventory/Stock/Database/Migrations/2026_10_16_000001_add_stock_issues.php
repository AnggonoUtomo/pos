<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::create('stock_issues', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('source_type', 32);
            $table->ulid('source_id');
            $table->string('source_number', 32);
            $table->char('request_hash', 64);
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('warehouse_code', 32);
            $table->string('warehouse_name', 150);
            $table->timestamp('posted_at');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['source_type', 'source_id']);
        });
        Schema::create('stock_issue_lines', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('issue_id')->constrained('stock_issues')->restrictOnDelete();
            $table->ulid('source_line_id')->unique();
            $table->unsignedSmallInteger('line_number');
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('base_unit_id')->constrained('units')->restrictOnDelete();
            $table->string('sku', 64);
            $table->string('item_name', 150);
            $table->string('base_unit_name', 150);
            $decimal($table, 'quantity', 20);
            $decimal($table, 'total_value', 30);
            $table->unique(['issue_id', 'line_number']);
        });
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->foreignUlid('opening_line_id')->nullable()->change();
            $table->foreignUlid('issue_line_id')->nullable()->unique()->constrained('stock_issue_lines')->restrictOnDelete();
            $table->string('source_number', 32)->nullable();
            $table->string('sku', 64)->nullable();
            $table->string('item_name', 150)->nullable();
            $table->string('base_unit_name', 150)->nullable();
            $table->string('warehouse_code', 32)->nullable();
            $table->string('warehouse_name', 150)->nullable();
        });
        Schema::create('fifo_consumptions', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('movement_id')->constrained('stock_movements')->restrictOnDelete();
            $table->foreignUlid('fifo_layer_id')->constrained('fifo_layers')->restrictOnDelete();
            $decimal($table, 'quantity', 20);
            $decimal($table, 'total_value', 30);
            $table->unique(['movement_id', 'fifo_layer_id']);
        });
    }

    public function down(): void
    {
        if (DB::table('stock_issues')->exists()) {
            throw new LogicException('Pengeluaran posted tidak boleh dihapus melalui rollback migration.');
        }
        Schema::dropIfExists('fifo_consumptions');
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropForeign(['issue_line_id']);
            $table->dropUnique(['issue_line_id']);
            $table->dropColumn('issue_line_id');
            $table->dropColumn(['source_number', 'sku', 'item_name', 'base_unit_name', 'warehouse_code', 'warehouse_name']);
        });
        Schema::table('stock_movements', fn (Blueprint $table) => $table->foreignUlid('opening_line_id')->nullable(false)->change());
        Schema::dropIfExists('stock_issue_lines');
        Schema::dropIfExists('stock_issues');
    }
};
