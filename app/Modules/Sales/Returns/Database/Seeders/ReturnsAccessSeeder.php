<?php

namespace App\Modules\Sales\Returns\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ReturnsAccessSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['sales.returns.view', 'sales.returns.manage'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }
}
