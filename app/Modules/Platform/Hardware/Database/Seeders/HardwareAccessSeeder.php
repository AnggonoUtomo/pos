<?php

namespace App\Modules\Platform\Hardware\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class HardwareAccessSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['view', 'manage', 'print', 'reprint'] as $action) {
            Permission::findOrCreate('platform.hardware.'.$action, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
