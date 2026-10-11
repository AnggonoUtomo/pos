<?php

namespace App\Modules\Platform\Identity\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class IdentityPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['platform.identity.view', 'platform.identity.manage', 'platform.identity.users.view',
            'platform.identity.users.create', 'platform.identity.users.update', 'platform.identity.users.manage'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
