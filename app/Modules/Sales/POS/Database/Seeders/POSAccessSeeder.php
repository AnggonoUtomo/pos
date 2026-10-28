<?php

namespace App\Modules\Sales\POS\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class POSAccessSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['sales.pos.invoices.create', 'sales.pos.invoices.post', 'sales.pos.invoices.view', 'sales.pos.shifts.view', 'sales.pos.shifts.review'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}
