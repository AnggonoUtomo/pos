<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->foreignUlid('payment_disbursement_id')->nullable()->unique()->constrained('payment_disbursements')->restrictOnDelete();
            $table->json('payment_snapshot')->nullable();
            $table->unsignedInteger('paid_from_version')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('purchase_orders')->whereNotNull('payment_disbursement_id')->exists()) {
            throw new LogicException('Pembayaran posted tidak boleh dihapus melalui rollback.');
        }
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropForeign(['payment_disbursement_id']);
            $table->dropUnique(['payment_disbursement_id']);
            $table->dropForeign(['paid_by']);
            $table->dropColumn(['payment_disbursement_id', 'payment_snapshot', 'paid_from_version', 'paid_at', 'paid_by']);
        });
    }
};
