<?php

namespace App\Modules\Reporting\OperationalReports\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class OperationalReportsAccessSeeder extends Seeder
{
    public function run(): void
    {
        Permission::findOrCreate('reporting.operational.view', 'web');
    }
}
