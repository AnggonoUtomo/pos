<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $money = static function (Blueprint $table, string $column): void {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->string($column, 31);
            } else {
                $table->decimal($column, 30, 2);
            }
        };
        Schema::create('payment_refunds', function (Blueprint $table) use ($money): void {
            $table->ulid('id')->primary();
            $table->string('source_type', 32);
            $table->ulid('source_id')->unique();
            $table->string('source_number', 32);
            $table->char('request_hash', 64);
            $money($table, 'total');
            $table->timestamp('paid_at');
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('payment_refund_lines', function (Blueprint $table) use ($money): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('refund_id')->constrained('payment_refunds')->restrictOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->foreignUlid('method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->foreignUlid('account_id')->constrained('payment_accounts')->restrictOnDelete();
            foreach (['method_code', 'account_code'] as $field) {
                $table->string($field, 32);
            }
            foreach (['method_name', 'account_name'] as $field) {
                $table->string($field, 150);
            }
            foreach (['method_type', 'account_type'] as $field) {
                $table->string($field, 32);
            }
            $money($table, 'amount');
            $table->unique(['refund_id', 'line_number']);
            $table->unique(['refund_id', 'method_id']);
        });
    }

    public function down(): void
    {
        if ((Schema::hasTable('payment_refunds') && DB::table('payment_refunds')->exists())
            || (Schema::hasTable('payment_refund_lines') && DB::table('payment_refund_lines')->exists())) {
            throw new LogicException('Refund posted tidak boleh dihapus melalui rollback.');
        }
        Schema::dropIfExists('payment_refund_lines');
        Schema::dropIfExists('payment_refunds');
    }
};
