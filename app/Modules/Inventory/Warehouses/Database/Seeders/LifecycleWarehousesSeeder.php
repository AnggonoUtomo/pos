<?php

namespace App\Modules\Inventory\Warehouses\Database\Seeders;

use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use Illuminate\Database\Seeder;

class LifecycleWarehousesSeeder extends Seeder
{
    public function run(array $context): array
    {
        foreach (['LAT-UTAMA' => 'Gudang Utama Latihan', 'LAT-CADANGAN' => 'Gudang Cadangan Latihan'] as $code => $name) {
            $context['warehouses'][$code] = Warehouse::create(['code' => $code, 'name' => $name, 'is_active' => true])->id;
        }

        return $context;
    }
}
