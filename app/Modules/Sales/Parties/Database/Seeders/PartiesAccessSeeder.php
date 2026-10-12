<?php

namespace App\Modules\Sales\Parties\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PartiesAccessSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        DB::transaction(function (): void {
            foreach (['sales.parties.view', 'sales.parties.manage'] as $name) {
                Permission::findOrCreate($name, 'web');
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
