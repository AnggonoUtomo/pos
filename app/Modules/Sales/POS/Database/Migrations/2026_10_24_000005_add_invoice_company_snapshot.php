<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', fn (Blueprint $table) => $table->json('company_snapshot')->nullable());
    }

    public function down(): void
    {
        if (DB::table('sales_invoices')->whereNotNull('company_snapshot')->exists()) {
            throw new LogicException('Snapshot identitas toko tidak boleh dihapus.');
        }
        Schema::table('sales_invoices', fn (Blueprint $table) => $table->dropColumn('company_snapshot'));
    }
};
