<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table): void {
            $table->foreignUlid('stock_issue_id')->nullable()->unique()->constrained('stock_issues')->restrictOnDelete();
            $table->unsignedInteger('posted_from_version')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('purchase_returns')->where('status', 'posted')->orWhereNotNull('stock_issue_id')->exists()) {
            throw new LogicException('Retur pembelian posted tidak boleh dihapus melalui rollback migration.');
        }
        Schema::table('purchase_returns', function (Blueprint $table): void {
            $table->dropForeign(['stock_issue_id']);
            $table->dropUnique(['stock_issue_id']);
            $table->dropForeign(['posted_by']);
            $table->dropColumn(['stock_issue_id', 'posted_from_version', 'posted_at', 'posted_by']);
        });
    }
};
