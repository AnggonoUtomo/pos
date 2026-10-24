<?php

namespace App\Modules\Platform\OperationalSettings\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class OperationalSettingsAccessSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['platform.operational-settings.view', 'platform.operational-settings.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}
