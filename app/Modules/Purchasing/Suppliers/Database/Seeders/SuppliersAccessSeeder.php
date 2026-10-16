<?php

namespace App\Modules\Purchasing\Suppliers\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SuppliersAccessSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['purchasing.suppliers.view', 'purchasing.suppliers.manage'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }
}
