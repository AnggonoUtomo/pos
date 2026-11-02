<?php

namespace App\Modules\Inventory\Items\Database\Seeders;

use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\ItemBrand;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\ItemCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class ItemClassificationsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo klasifikasi hanya local/testing.');
        }
        DB::transaction(function (): void {
            foreach (['DEMO-CCTV' => 'Kamera dan perekam CCTV', 'DEMO-ELECTRICAL' => 'Kelistrikan dan kabel'] as $code => $name) {
                ItemCategory::withTrashed()->firstOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
            }
            foreach (['DEMO-VISION' => 'Vision Demo', 'DEMO-VOLT' => 'Volt Demo'] as $code => $name) {
                ItemBrand::withTrashed()->firstOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
            }
        });
    }
}
