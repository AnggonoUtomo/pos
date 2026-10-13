<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_prices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignUlid('customer_level_id')->constrained('customer_levels')->restrictOnDelete();
            $table->foreign(['item_id', 'unit_id'])->references(['item_id', 'unit_id'])->on('item_units')->restrictOnDelete();
            $table->unique(['item_id', 'unit_id', 'customer_level_id']);
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->string('amount', 21);
            } else {
                $table->decimal('amount', 20, 2);
            }
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_prices');
    }
};
