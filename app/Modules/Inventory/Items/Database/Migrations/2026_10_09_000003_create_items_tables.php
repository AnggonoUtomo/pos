<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('sku', 64)->unique();
            $table->string('name', 150);
            $table->foreignUlid('base_unit_id')->constrained('units')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('item_units', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('conversion_ratio', 20, 6);
            $table->string('barcode', 64)->nullable()->unique();
            $table->boolean('is_sale_unit')->default(true);
            $table->boolean('is_purchase_unit')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['item_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_units');
        Schema::dropIfExists('items');
    }
};
