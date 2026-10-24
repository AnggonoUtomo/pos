<?php

namespace App\Modules\Inventory\Transfers\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class TransfersAccessSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['inventory.transfers.view', 'inventory.transfers.manage', 'inventory.transfers.post'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }
}
