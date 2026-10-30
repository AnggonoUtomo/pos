<?php

namespace App\Modules\Platform\AuditLog\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AuditLogAccessSeeder extends Seeder
{
    public function run(): void
    {
        Permission::findOrCreate('platform.audit-log.view', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
