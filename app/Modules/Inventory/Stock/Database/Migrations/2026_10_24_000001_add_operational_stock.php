<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_receipt_lines', fn (Blueprint $table) => $table->foreignUlid('origin_fifo_layer_id')->nullable()->constrained('fifo_layers')->restrictOnDelete());
        Schema::create('stock_adjustments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('number', 32)->unique();
            $table->char('request_hash', 64);
            $table->ulid('warehouse_id')->index();
            $table->ulid('item_id')->index();
            $table->string('direction', 8);
            foreach (['quantity' => 20, 'total_value' => 30] as $column => $precision) {
                if (Schema::getConnection()->getDriverName() === 'sqlite') {
                    $table->string($column, $precision + 1);
                } else {
                    $table->decimal($column, $precision, 6);
                }
            }
            $table->string('reason', 500);
            $table->timestamp('posted_at');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('stock_adjustments')->exists() || DB::table('stock_receipt_lines')->whereNotNull('origin_fifo_layer_id')->exists()) {
            throw new LogicException('Ledger operasional tidak boleh dihapus melalui rollback.');
        }
        Schema::dropIfExists('stock_adjustments');
        Schema::table('stock_receipt_lines', function (Blueprint $table): void {
            $table->dropForeign(['origin_fifo_layer_id']);
            $table->dropColumn('origin_fifo_layer_id');
        });
    }
};
