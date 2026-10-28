<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name', 255);
            $table->foreignUlid('account_id')->constrained('payment_accounts')->restrictOnDelete();
            $table->string('account_code', 32);
            $table->string('account_name', 150);
            $table->foreignId('active_actor_id')->nullable()->unique()->constrained('users')->restrictOnDelete();
            $table->foreignUlid('active_account_id')->nullable()->unique()->constrained('payment_accounts')->restrictOnDelete();
            $table->string('status', 16);
            $table->string('review_status', 16);
            $table->char('open_hash', 64);
            $table->char('close_hash', 64)->nullable();
            foreach (['opening_balance', 'expected_cash', 'counted_cash', 'difference'] as $column) {
                if (Schema::getConnection()->getDriverName() === 'sqlite') {
                    $table->string($column, 41)->nullable();
                } else {
                    $table->decimal($column, 38, 6)->nullable();
                }
            }
            $table->string('close_reason', 200)->nullable();
            $table->json('summary')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('review_note', 200)->nullable();
            $table->index(['actor_id', 'opened_at']);
        });
        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->foreignUlid('cashier_shift_id')->nullable()->constrained('cashier_shifts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('cashier_shifts')->exists() || DB::table('sales_invoices')->whereNotNull('cashier_shift_id')->exists()) {
            throw new LogicException('Shift berisi data tidak boleh dihapus melalui rollback.');
        }
        Schema::table('sales_invoices', fn (Blueprint $table) => $table->dropConstrainedForeignId('cashier_shift_id'));
        Schema::dropIfExists('cashier_shifts');
    }
};
