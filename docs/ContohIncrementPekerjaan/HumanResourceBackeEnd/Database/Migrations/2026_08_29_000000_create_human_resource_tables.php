<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', static function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('primary_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('employee_no', 40)->unique();
            $table->string('name', 180);
            $table->string('preferred_name', 120)->nullable();
            $table->string('employment_type', 40)->index();
            $table->string('position', 120)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->date('joined_on')->nullable();
            $table->date('left_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['primary_unit_id', 'status']);
            $table->index(['employment_type', 'status']);
            $table->index(['joined_on', 'left_on']);
        });

        Schema::create('employee_unit_assignments', static function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignUlid('organization_unit_id')->constrained('organization_units')->cascadeOnDelete();
            $table->string('role', 40)->index();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->timestamps();

            $table->index(['employee_id', 'is_primary']);
            $table->index(['organization_unit_id', 'role']);
            $table->index(['starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_unit_assignments');
        Schema::dropIfExists('employees');
    }
};
