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
                $table->decimal($column, 30, 6);
            }
        };
        Schema::create('payment_disbursements', function (Blueprint $table) use ($money): void {
            $table->ulid('id')->primary();
            $table->string('source_type', 32);
            $table->ulid('source_id');
            $table->string('source_number', 32);
            $table->char('request_hash', 64);
            $money($table, 'total');
            $table->timestamp('paid_at');
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['source_type', 'source_id']);
        });
        Schema::create('payment_disbursement_lines', function (Blueprint $table) use ($money): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('disbursement_id')->constrained('payment_disbursements')->restrictOnDelete();
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
            $table->unique(['disbursement_id', 'line_number']);
            $table->unique(['disbursement_id', 'method_id']);
        });
    }

    public function down(): void
    {
        if (DB::table('payment_disbursements')->exists()) {
            throw new LogicException('Pengeluaran posted tidak boleh dihapus melalui rollback.');
        }
        Schema::dropIfExists('payment_disbursement_lines');
        Schema::dropIfExists('payment_disbursements');
    }
};
