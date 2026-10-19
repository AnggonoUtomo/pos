<?php

namespace App\Modules\Purchasing\Returns\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

final class ReturnsAccessSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['purchasing.returns.view', 'purchasing.returns.manage'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }
}
