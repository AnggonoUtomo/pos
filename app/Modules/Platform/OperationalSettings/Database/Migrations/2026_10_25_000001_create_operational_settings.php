<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_settings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('receipt_paper', 10)->default('a4');
            $table->boolean('backup_enabled')->default(false);
            $table->string('backup_time', 5)->default('21:00');
            $table->unsignedInteger('version')->default(0);
            $table->timestamp('scheduler_seen_at')->nullable();
            $table->timestamps();
        });
        DB::table('operational_settings')->insert(['id' => '00000000000000000000000002', 'created_at' => now(), 'updated_at' => now()]);
        Schema::create('operational_backup_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->date('scheduled_for')->unique();
            $table->string('status', 20);
            $table->unsignedTinyInteger('attempts');
            $table->ulid('token');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('backup_file')->nullable();
            $table->string('error_code', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (DB::table('operational_backup_runs')->exists() || DB::table('operational_settings')->where('version', '>', 0)->exists()) {
            throw new LogicException('Pengaturan operasional dan riwayat backup tidak boleh dihapus melalui rollback.');
        }
        Schema::dropIfExists('operational_backup_runs');
        Schema::dropIfExists('operational_settings');
    }
};
