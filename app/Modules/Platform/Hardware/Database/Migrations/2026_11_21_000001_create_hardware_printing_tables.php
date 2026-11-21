<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hardware_printers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name', 150);
            $table->string('printer_name', 150);
            $table->string('gateway_host', 253);
            $table->json('specification');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('hardware_label_jobs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('printer_id')->constrained('hardware_printers')->restrictOnDelete();
            $table->unsignedBigInteger('created_by');
            $table->string('idempotency_key', 64);
            $table->char('request_hash', 64);
            $table->string('kind', 30);
            $table->string('status', 30)->default('queued');
            $table->json('snapshot');
            $table->json('printer_snapshot');
            $table->unsignedSmallInteger('copies');
            $table->foreignUlid('original_job_id')->nullable()->constrained('hardware_label_jobs')->restrictOnDelete();
            $table->string('reason', 500)->nullable();
            $table->timestamps();
            $table->unique(['created_by', 'idempotency_key'], 'hardware_job_idempotency');
            $table->index(['created_by', 'created_at']);
            $table->index(['printer_id', 'status']);
        });
        Schema::create('hardware_print_attempts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('job_id')->unique()->constrained('hardware_label_jobs')->restrictOnDelete();
            $table->char('claim_hash', 64);
            $table->timestamp('expires_at');
            $table->string('status', 30)->default('sending');
            $table->string('error_code', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hardware_print_attempts');
        Schema::dropIfExists('hardware_label_jobs');
        Schema::dropIfExists('hardware_printers');
    }
};
