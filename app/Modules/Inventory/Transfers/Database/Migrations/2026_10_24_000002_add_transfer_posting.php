<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table): void {
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('stock_transfers')->where('status', 'posted')->exists()) {
            throw new LogicException('Transfer posted tidak boleh dihapus melalui rollback.');
        }
        Schema::table('stock_transfers', function (Blueprint $table): void {
            $table->dropForeign(['posted_by']);
            $table->dropColumn(['posted_at', 'posted_by']);
        });
    }
};
