<?php

namespace App\Modules\Sales\POS\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class POSAccessSeeder extends Seeder
{
    public function run(): void
    {
        Permission::findOrCreate('sales.pos.invoices.create', 'web');
    }
}
