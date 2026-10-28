<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_held_carts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('actor_id');
            $table->unsignedInteger('version');
            $table->string('label', 80)->default('');
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('warehouse_name', 150);
            $table->string('customer_name', 150);
            $table->json('payload');
            $table->json('line_snapshots');
            $table->string('request_hash', 64);
            $table->foreignUlid('invoice_id')->nullable()->unique()->constrained('sales_invoices')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['actor_id', 'invoice_id', 'deleted_at', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_held_carts');
    }
};
