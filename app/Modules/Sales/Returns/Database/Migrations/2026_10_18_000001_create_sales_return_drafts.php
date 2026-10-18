<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('number', 32)->unique();
            $table->foreignUlid('invoice_id')->constrained('sales_invoices')->restrictOnDelete();
            $table->string('invoice_number', 32)->index();
            $table->string('customer_name', 150);
            $table->json('snapshot');
            $table->string('status', 16)->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->string('notes', 500)->nullable();
            $table->string('demo_key', 64)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('sales_return_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('sales_return_id')->constrained('sales_returns')->restrictOnDelete();
            $table->foreignUlid('invoice_line_id')->constrained('sales_invoice_lines')->restrictOnDelete();
            $table->json('snapshot');
            foreach (['quantity', 'base_quantity'] as $column) {
                if (Schema::getConnection()->getDriverName() === 'sqlite') {
                    $table->string($column, 32);
                } else {
                    $table->decimal($column, 20, 6);
                }
            }
            $table->unique(['sales_return_id', 'invoice_line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_lines');
        Schema::dropIfExists('sales_returns');
    }
};
