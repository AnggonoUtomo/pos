<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $decimal = static function (Blueprint $table, string $column, int $precision, int $scale): void {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->string($column, $precision + 1);
            } else {
                $table->decimal($column, $precision, $scale);
            }
        };
        Schema::create('purchase_return_refund_scopes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('purchase_order_id')->unique();
            $table->unsignedInteger('revision')->default(0);
        });
        Schema::create('purchase_return_settlements', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_return_id')->unique()->constrained('purchase_returns')->restrictOnDelete();
            $table->foreignUlid('scope_id')->constrained('purchase_return_refund_scopes')->restrictOnDelete();
            $table->string('outcome', 32);
            $decimal($table, 'total', 30, 6);
            $table->ulid('finance_receipt_id')->nullable()->unique();
            $table->unsignedInteger('from_version');
            $table->unsignedInteger('scope_revision');
            $table->char('quote_fingerprint', 64);
            $table->char('request_hash', 64);
            $table->json('snapshot');
            $table->timestamp('settled_at');
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('purchase_return_refund_allocations', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('settlement_id')->constrained('purchase_return_settlements')->restrictOnDelete();
            $table->ulid('purchase_return_line_id')->unique('prra_return_line_unique');
            $table->foreign('purchase_return_line_id', 'prra_return_line_fk')->references('id')->on('purchase_return_lines')->restrictOnDelete();
            $table->ulid('purchase_order_line_id')->index();
            $decimal($table, 'quantity', 20, 6);
            $decimal($table, 'amount', 30, 6);
            $table->unique(['settlement_id', 'purchase_order_line_id'], 'pr_refund_allocation_source_unique');
        });
    }

    public function down(): void
    {
        if ((Schema::hasTable('purchase_return_settlements') && DB::table('purchase_return_settlements')->exists())
            || (Schema::hasTable('purchase_return_refund_allocations') && DB::table('purchase_return_refund_allocations')->exists())
            || (Schema::hasTable('purchase_return_refund_scopes') && DB::table('purchase_return_refund_scopes')->where('revision', '>', 0)->exists())) {
            throw new LogicException('Penyelesaian refund posted tidak boleh dihapus melalui rollback.');
        }
        Schema::dropIfExists('purchase_return_refund_allocations');
        Schema::dropIfExists('purchase_return_settlements');
        Schema::dropIfExists('purchase_return_refund_scopes');
    }
};
