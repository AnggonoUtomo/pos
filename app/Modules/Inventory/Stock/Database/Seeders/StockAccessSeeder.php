<?php

namespace App\Modules\Inventory\Stock\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class StockAccessSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        DB::transaction(function (): void {
            foreach (['inventory.stock.view', 'inventory.stock.manage', 'inventory.stock.post'] as $name) {
                Permission::findOrCreate($name, 'web');
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
