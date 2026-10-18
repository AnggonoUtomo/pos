<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_return_allocations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('receipt_line_id')->constrained('stock_receipt_lines')->restrictOnDelete();
            $table->foreignUlid('fifo_consumption_id')->constrained('fifo_consumptions')->restrictOnDelete();
            foreach (['quantity' => 20, 'total_value' => 30] as $column => $digits) {
                if (Schema::getConnection()->getDriverName() === 'sqlite') {
                    $table->string($column, $digits + 1);
                } else {
                    $table->decimal($column, $digits, 6);
                }
            }
            $table->unique(['receipt_line_id', 'fifo_consumption_id'], 'stock_return_allocation_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('stock_return_allocations')->exists() || DB::table('stock_receipts')->where('source_type', 'sale_return')->exists()) {
            throw new LogicException('Retur posted tidak boleh dihapus melalui rollback migration.');
        }
        Schema::dropIfExists('stock_return_allocations');
    }
};
