<?php

namespace App\Modules\Purchasing\PurchaseOrders\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PurchaseOrdersAccessSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['purchasing.purchase-orders.view', 'purchasing.purchase-orders.manage', 'purchasing.purchase-orders.post', 'purchasing.purchase-orders.pay'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }
}
