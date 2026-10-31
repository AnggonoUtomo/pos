<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_minimums', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('base_unit_id')->constrained('units')->restrictOnDelete();
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->string('minimum_quantity', 21);
            } else {
                $table->decimal('minimum_quantity', 20, 6);
            }
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['warehouse_id', 'item_id']);
        });
    }

    public function down(): void
    {
        if (DB::table('stock_minimums')->exists()) {
            throw new LogicException('Pengaturan stok minimum berhistori tidak boleh dihapus melalui rollback.');
        }
        Schema::dropIfExists('stock_minimums');
    }
};
