<?php

namespace App\Modules\Finance\Payments\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PaymentsAccessSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['finance.payments.view', 'finance.payments.manage', 'finance.payments.history.view', 'finance.cashbook.view', 'finance.cashbook.open', 'finance.cashbook.manage', 'finance.cashbook.reverse'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}
