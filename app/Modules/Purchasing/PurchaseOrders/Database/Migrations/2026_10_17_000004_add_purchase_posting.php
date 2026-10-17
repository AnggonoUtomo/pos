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
            $table->foreignUlid('stock_receipt_id')->nullable()->unique()->constrained('stock_receipts')->restrictOnDelete();
            $table->unsignedInteger('posted_from_version')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('status');
        });
    }

    public function down(): void
    {
        if (DB::table('purchase_orders')->where('status', 'posted')->orWhereNotNull('stock_receipt_id')->exists()) {
            throw new LogicException('Penerimaan posted tidak boleh dihapus lewat rollback migration.');
        }
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropForeign(['stock_receipt_id']);
            $table->dropUnique(['stock_receipt_id']);
            $table->dropForeign(['posted_by']);
            $table->dropIndex(['status']);
            $table->dropColumn(['stock_receipt_id', 'posted_from_version', 'posted_at', 'posted_by']);
        });
    }
};
