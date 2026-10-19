<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $decimal = static function (Blueprint $table, string $name): void {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->string($name, 21);
            } else {
                $table->decimal($name, 20, 6);
            }
        };

        Schema::create('purchase_returns', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('number', 32)->unique();
            $table->foreignUlid('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->string('purchase_order_number', 32)->index();
            $table->string('supplier_name', 150)->index();
            $table->json('snapshot');
            $table->string('status', 16)->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->string('notes', 500)->nullable();
            $table->string('demo_key', 64)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_return_lines', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_return_id')->constrained('purchase_returns')->restrictOnDelete();
            $table->foreignUlid('purchase_order_line_id')->constrained('purchase_order_lines')->restrictOnDelete();
            $decimal($table, 'quantity');
            $decimal($table, 'base_quantity');
            $table->json('snapshot');
            $table->unique(['purchase_return_id', 'purchase_order_line_id'], 'pr_return_line_source_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_returns') && DB::table('purchase_returns')->exists()) {
            throw new LogicException('Migration retur pembelian tidak dapat dibatalkan selama draft masih tersimpan.');
        }

        if (Schema::hasTable('purchase_return_lines') && DB::table('purchase_return_lines')->exists()) {
            throw new LogicException('Migration retur pembelian tidak dapat dibatalkan selama baris retur masih tersimpan.');
        }

        Schema::dropIfExists('purchase_return_lines');
        Schema::dropIfExists('purchase_returns');
    }
};
