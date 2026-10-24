<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 150);
            $table->string('address', 500);
            $table->string('phone', 50);
            $table->string('receipt_footer', 300);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (DB::table('company_settings')->exists()) {
            throw new LogicException('Identitas toko atau snapshot struk tidak boleh dihapus melalui rollback.');
        }
        Schema::dropIfExists('company_settings');
    }
};
