<?php

namespace App\Modules\Inventory\Warehouses\Database\Seeders;

use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class WarehousesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo gudang hanya boleh dijalankan pada environment local/testing.');
        }

        DB::transaction(function (): void {
            foreach (['WH-MAIN' => 'Gudang Utama', 'WH-SECONDARY' => 'Gudang Tambahan'] as $code => $name) {
                Warehouse::withTrashed()->firstOrCreate(
                    ['code' => $code],
                    ['name' => $name, 'is_active' => true],
                );
            }
        });
    }
}
