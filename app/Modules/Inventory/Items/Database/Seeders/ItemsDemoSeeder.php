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
            ['sku' => 'DEMO-WATER-600', 'name' => 'Air Mineral 600 ml', 'base' => 'PCS', 'units' => ['PCS' => '1', 'BOX' => '12']],
            ['sku' => 'DEMO-TEA-250', 'name' => 'Teh Kemasan 250 ml', 'base' => 'PCS', 'units' => ['PCS' => '1', 'PACK' => '6', 'BOX' => '24']],
            ['sku' => 'DEMO-RICE', 'name' => 'Beras', 'base' => 'G', 'units' => ['G' => '1', 'KG' => '1000']],
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
