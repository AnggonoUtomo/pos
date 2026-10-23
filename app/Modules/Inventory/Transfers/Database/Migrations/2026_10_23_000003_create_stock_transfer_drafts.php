<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('number', 32)->unique();
            foreach (['source', 'destination'] as $prefix) {
                $table->ulid($prefix.'_warehouse_id')->index();
                $table->string($prefix.'_warehouse_code', 32);
                $table->string($prefix.'_warehouse_name', 150);
            }
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->string('notes', 500)->nullable();
            $table->string('demo_key', 64)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('stock_transfer_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('transfer_id')->constrained('stock_transfers')->restrictOnDelete();
            foreach (['item_id', 'unit_id', 'base_unit_id'] as $column) {
                $table->ulid($column);
            }
            $table->string('sku', 64);
            foreach (['item_name', 'unit_name', 'base_unit_name'] as $column) {
                $table->string($column, 150);
            }
            foreach (['quantity', 'conversion_ratio', 'base_quantity'] as $column) {
                if (Schema::getConnection()->getDriverName() === 'sqlite') {
                    $table->string($column, 21);
                } else {
                    $table->decimal($column, 20, 6);
                }
            }
            $table->unique(['transfer_id', 'item_id']);
        });
    }

    public function down(): void
    {
        if ((Schema::hasTable('stock_transfers') && DB::table('stock_transfers')->exists())
            || (Schema::hasTable('stock_transfer_lines') && DB::table('stock_transfer_lines')->exists())) {
            throw new LogicException('Draft transfer berisi data tidak boleh dihapus melalui rollback.');
        }
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
    }
};
