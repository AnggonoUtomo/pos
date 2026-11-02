<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['item_categories', 'item_brands'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('code', 32)->unique();
                $table->string('name', 150);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        Schema::table('items', function (Blueprint $table): void {
            $table->foreignUlid('category_id')->nullable()->constrained('item_categories')->restrictOnDelete();
            $table->foreignUlid('brand_id')->nullable()->constrained('item_brands')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('brand_id');
        });
        Schema::dropIfExists('item_brands');
        Schema::dropIfExists('item_categories');
    }
};
