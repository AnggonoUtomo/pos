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
        Schema::create('stock_receipts', function (Blueprint $table): void {
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
        Schema::create('stock_receipt_lines', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('receipt_id')->constrained('stock_receipts')->restrictOnDelete();
            $table->ulid('source_line_id')->unique();
            $table->unsignedSmallInteger('line_number');
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('base_unit_id')->constrained('units')->restrictOnDelete();
            $table->string('sku', 64);
            $table->string('item_name', 150);
            $table->string('base_unit_name', 150);
            $decimal($table, 'quantity', 20);
            $decimal($table, 'total_value', 30);
            $table->unique(['receipt_id', 'line_number']);
        });
        Schema::table('stock_movements', fn (Blueprint $table) => $table->foreignUlid('receipt_line_id')->nullable()->unique()->constrained('stock_receipt_lines')->restrictOnDelete());
        Schema::table('stock_balances', function (Blueprint $table): void {
            $table->foreignUlid('opening_line_id')->nullable()->change();
            $table->string('sku', 64)->nullable();
            $table->string('item_name', 150)->nullable();
            $table->string('base_unit_name', 150)->nullable();
            $table->string('warehouse_code', 32)->nullable();
            $table->string('warehouse_name', 150)->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('stock_receipts')->exists() || DB::table('stock_balances')->whereNull('opening_line_id')->exists()) {
            throw new LogicException('Receipt posted atau saldo tanpa opening tidak boleh dihapus melalui rollback migration.');
        }
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropForeign(['receipt_line_id']);
            $table->dropUnique(['receipt_line_id']);
            $table->dropColumn('receipt_line_id');
        });
        Schema::table('stock_balances', function (Blueprint $table): void {
            $table->dropColumn(['sku', 'item_name', 'base_unit_name', 'warehouse_code', 'warehouse_name']);
            $table->foreignUlid('opening_line_id')->nullable(false)->change();
        });
        Schema::dropIfExists('stock_receipt_lines');
        Schema::dropIfExists('stock_receipts');
    }
};
