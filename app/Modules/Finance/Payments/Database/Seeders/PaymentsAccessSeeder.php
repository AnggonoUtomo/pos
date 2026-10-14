<?php

namespace App\Modules\Finance\Payments\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PaymentsAccessSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['finance.payments.view', 'finance.payments.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}
