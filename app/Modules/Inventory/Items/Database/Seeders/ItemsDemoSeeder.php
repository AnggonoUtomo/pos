<?php

namespace App\Modules\Inventory\Items\Database\Seeders;

use App\Modules\Inventory\Items\Application\Actions\CreateItem;
use App\Modules\Inventory\Items\Application\Contracts\ItemRepository;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Application\DTOs\ItemUnitData;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class ItemsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Seeder demo barang hanya boleh dijalankan pada local/testing.');
        }

        $items = app(ItemRepository::class);
        $units = app(UnitLookup::class);
        $create = app(CreateItem::class);
        $demo = [
            ['sku' => 'DEMO-CCTV-IP-2MP', 'name' => 'Kamera IP 2MP', 'base' => 'UNIT', 'units' => ['UNIT' => '1', 'BOX' => '4']],
            ['sku' => 'DEMO-CCTV-NVR-8CH', 'name' => 'NVR 8 Kanal', 'base' => 'UNIT', 'units' => ['UNIT' => '1', 'BOX' => '2']],
            ['sku' => 'DEMO-CCTV-UTP-CAT6', 'name' => 'Kabel UTP Cat6', 'base' => 'M', 'units' => ['M' => '1', 'ROLL' => '305']],
            ['sku' => 'DEMO-ELEC-NYM-3X15', 'name' => 'Kabel NYM 3x1.5 mm2', 'base' => 'M', 'units' => ['M' => '1', 'ROLL' => '100']],
            ['sku' => 'DEMO-ELEC-MCB-6A', 'name' => 'MCB 1P 6A', 'base' => 'UNIT', 'units' => ['UNIT' => '1', 'BOX' => '12']],
            ['sku' => 'DEMO-CCTV-RJ45', 'name' => 'Konektor RJ45', 'base' => 'PCS', 'units' => ['PCS' => '1', 'PACK' => '100', 'BOX' => '1000']],
            ['sku' => 'DEMO-CCTV-ADAPTER-12V', 'name' => 'Adaptor CCTV 12V', 'base' => 'UNIT', 'units' => ['UNIT' => '1', 'BOX' => '10']],
            ['sku' => 'DEMO-CCTV-BALUN', 'name' => 'Balun Video', 'base' => 'PCS', 'units' => ['PCS' => '1', 'PAIR' => '2']],
            ['sku' => 'DEMO-CCTV-KIT', 'name' => 'Paket CCTV', 'base' => 'SET', 'units' => ['SET' => '1']],
        ];

        DB::transaction(function () use ($items, $units, $create, $demo): void {
            foreach ($demo as $data) {
                if ($items->findIdBySku($data['sku']) !== null) {
                    continue;
                }

                $relations = [];
                $ids = [];
                foreach ($data['units'] as $code => $ratio) {
                    $unit = $units->findActiveByCode($code);
                    if ($unit === null) {
                        throw new LogicException("Satuan {$code} harus aktif; jalankan UnitsDemoSeeder terlebih dahulu.");
                    }

                    $ids[$code] = $unit->id;
                    $relations[] = new ItemUnitData($unit->id, $ratio, "{$data['sku']}-{$code}");
                }

                $create->handle(new CreateItemData($data['sku'], $data['name'], $ids[$data['base']], $relations));
            }
        });
    }
}
