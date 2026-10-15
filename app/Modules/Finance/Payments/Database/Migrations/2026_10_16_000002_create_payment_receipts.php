<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_receipts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('source_type', 16);
            $table->ulid('source_id');
            $table->string('source_number', 32);
            $table->char('request_hash', 64);
            $table->decimal('total', 30, 2);
            $table->decimal('tendered', 30, 2);
            $table->decimal('change', 30, 2);
            $table->timestamp('posted_at');
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->unique(['source_type', 'source_id']);
        });
        Schema::create('payment_records', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('receipt_id')->constrained('payment_receipts')->restrictOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->foreignUlid('method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->foreignUlid('account_id')->constrained('payment_accounts')->restrictOnDelete();
            $table->string('method_code', 32);
            $table->string('method_name', 150);
            $table->string('method_type', 16);
            $table->string('account_code', 32);
            $table->string('account_name', 150);
            $table->string('account_type', 16);
            $table->decimal('tendered_amount', 30, 2);
            $table->decimal('applied_amount', 30, 2);
            $table->decimal('change_amount', 30, 2);
            $table->unique(['receipt_id', 'line_number']);
            $table->unique(['receipt_id', 'method_id']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('payment_receipts') && DB::table('payment_receipts')->exists()) {
            throw new LogicException('Penerimaan posted tidak boleh dihapus lewat rollback migration.');
        }
        Schema::dropIfExists('payment_records');
        Schema::dropIfExists('payment_receipts');
    }
};
