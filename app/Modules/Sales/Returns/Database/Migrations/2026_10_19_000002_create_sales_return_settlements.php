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
        Schema::create('sales_return_refund_scopes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('invoice_id')->unique();
            $table->unsignedInteger('revision')->default(0);
        });
        Schema::create('sales_return_settlements', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('sales_return_id')->unique()->constrained('sales_returns')->restrictOnDelete();
            $table->foreignUlid('scope_id')->constrained('sales_return_refund_scopes')->restrictOnDelete();
            $table->string('outcome', 32);
            $decimal($table, 'total', 30, 2);
            $table->ulid('finance_refund_id')->nullable()->unique();
            $table->unsignedInteger('from_version');
            $table->unsignedInteger('scope_revision');
            $table->char('quote_fingerprint', 64);
            $table->char('request_hash', 64);
            $table->json('snapshot');
            $table->timestamp('settled_at');
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('sales_return_refund_allocations', function (Blueprint $table) use ($decimal): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('settlement_id')->constrained('sales_return_settlements')->restrictOnDelete();
            $table->foreignUlid('sales_return_line_id')->unique()->constrained('sales_return_lines')->restrictOnDelete();
            $table->ulid('invoice_line_id')->index();
            $decimal($table, 'quantity', 20, 6);
            $decimal($table, 'amount', 30, 2);
            $table->unique(['settlement_id', 'invoice_line_id'], 'sr_refund_allocation_source_unique');
        });
    }

    public function down(): void
    {
        if ((Schema::hasTable('sales_return_settlements') && DB::table('sales_return_settlements')->exists())
            || (Schema::hasTable('sales_return_refund_allocations') && DB::table('sales_return_refund_allocations')->exists())
            || (Schema::hasTable('sales_return_refund_scopes') && DB::table('sales_return_refund_scopes')->where('revision', '>', 0)->exists())) {
            throw new LogicException('Penyelesaian refund posted tidak boleh dihapus melalui rollback.');
        }
        Schema::dropIfExists('sales_return_refund_allocations');
        Schema::dropIfExists('sales_return_settlements');
        Schema::dropIfExists('sales_return_refund_scopes');
    }
};
