<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('subject_id', 26)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Keep the wider column so recorded ULID subjects remain intact on rollback.
    }
};
