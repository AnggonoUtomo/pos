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
        Schema::create('purchase_refund_receipts', function (Blueprint $table) use ($money): void {
            $table->ulid('id')->primary();
            $table->string('source_type', 32);
            $table->ulid('source_id')->unique();
            $table->string('source_number', 32);
            $table->ulid('purchase_id')->index();
            $table->foreignUlid('disbursement_id')->constrained('payment_disbursements')->restrictOnDelete();
            $table->char('request_hash', 64);
            $money($table, 'total');
            $table->timestamp('received_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('purchase_refund_receipt_lines', function (Blueprint $table) use ($money): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('receipt_id')->constrained('purchase_refund_receipts')->restrictOnDelete();
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
            $table->unique(['receipt_id', 'line_number']);
            $table->unique(['receipt_id', 'method_id']);
        });
    }

    public function down(): void
    {
        if ((Schema::hasTable('purchase_refund_receipts') && DB::table('purchase_refund_receipts')->exists())
            || (Schema::hasTable('purchase_refund_receipt_lines') && DB::table('purchase_refund_receipt_lines')->exists())) {
            throw new LogicException('Refund posted tidak boleh dihapus melalui rollback.');
        }
        Schema::dropIfExists('purchase_refund_receipt_lines');
        Schema::dropIfExists('purchase_refund_receipts');
    }
};
