<?php

namespace App\Modules\Inventory\Items\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ItemsAccessSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function (): void {
            foreach (['inventory.view', 'inventory.manage'] as $name) {
                Permission::findOrCreate($name, 'web');
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
