<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operational_settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('table_per_page')->default(10);
        });
    }

    public function down(): void
    {
        Schema::table('operational_settings', function (Blueprint $table): void {
            $table->dropColumn('table_per_page');
        });
    }
};
