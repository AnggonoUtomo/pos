<?php

namespace App\Modules\Platform\CompanySettings\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class CompanySettingsAccessSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['platform.company-settings.view', 'platform.company-settings.manage'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }
}
