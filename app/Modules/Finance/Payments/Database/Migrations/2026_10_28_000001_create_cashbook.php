<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $money = static function (Blueprint $table): void {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->string('amount', 32);
            } else {
                $table->decimal('amount', 30, 6);
            }
        };
        Schema::create('cashbook_openings', function (Blueprint $table) use ($money): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('account_id')->unique()->constrained('payment_accounts')->restrictOnDelete();
            $table->date('starts_on');
            $money($table);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');
        });
        Schema::create('cashbook_entries', function (Blueprint $table) use ($money): void {
            $table->ulid('id')->primary();
            $table->string('idempotency_key', 64)->unique();
            $table->char('request_hash', 64);
            $table->string('kind', 16);
            $table->string('direction', 16);
            $table->foreignUlid('account_id')->constrained('payment_accounts')->restrictOnDelete();
            $table->foreignUlid('counter_account_id')->nullable()->constrained('payment_accounts')->restrictOnDelete();
            foreach (['account', 'counter_account'] as $prefix) {
                $table->string($prefix.'_code', 32)->nullable();
                $table->string($prefix.'_name', 150)->nullable();
                $table->string($prefix.'_type', 16)->nullable();
            }
            $money($table);
            $table->string('description', 200);
            $table->timestamp('occurred_at');
            $table->timestamp('posted_at');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('reversed_entry_id')->nullable()->unique()->constrained('cashbook_entries')->restrictOnDelete();
            $table->index(['account_id', 'occurred_at']);
            $table->index(['counter_account_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        if (DB::table('cashbook_openings')->exists() || DB::table('cashbook_entries')->exists()) {
            throw new LogicException('Buku Kas berisi data tidak boleh dihapus melalui rollback.');
        }
        Schema::dropIfExists('cashbook_entries');
        Schema::dropIfExists('cashbook_openings');
    }
};
