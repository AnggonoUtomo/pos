<?php

namespace App\Modules\Inventory\Items\Database\Seeders;

use App\Modules\Inventory\Items\Application\Actions\CreateItem;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Application\DTOs\ItemUnitData;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceCatalog;
use App\Modules\Sales\Parties\Application\DTOs\ItemPriceData;
use Brick\Math\BigDecimal;
use Illuminate\Database\Seeder;

class LifecycleItemsSeeder extends Seeder
{
    public function run(array $context): array
    {
        foreach ([
            'KAMERA' => ['Kamera IP 2MP Latihan', 'UNIT', 'BOX', '4', ['500000', '475000', '450000']],
            'NVR' => ['NVR 8 Kanal Latihan', 'UNIT', 'BOX', '2', ['1600000', '1500000', '1400000']],
            'UTP' => ['Kabel UTP Cat6 Latihan', 'M', 'ROLL', '305', ['6000', '5500', '5000']],
            'NYM' => ['Kabel NYM 3x1.5 mm2 Latihan', 'M', 'ROLL', '100', ['15000', '14000', '13500']],
            'MCB' => ['MCB 1P 6A Latihan', 'UNIT', 'BOX', '12', ['55000', '50000', '47500']],
            'RJ45' => ['Konektor RJ45 Latihan', 'PCS', 'PACK', '100', ['2500', '2000', '1750']],
        ] as $key => [$name, $base, $pack, $ratio, $amounts]) {
            $units = [new ItemUnitData($context['units'][$base], '1', 'LAT-'.$key.'-'.$base),
                new ItemUnitData($context['units'][$pack], $ratio, 'LAT-'.$key.'-'.$pack)];
            $id = app(CreateItem::class)->handle(new CreateItemData('LAT-'.$key, $name, $context['units'][$base], $units));
            $prices = [];
            foreach ($units as $unit) {
                foreach (array_values($context['levels']) as $index => $level) {
                    $prices[] = new ItemPriceData($unit->unitId, $level, (string) BigDecimal::of($amounts[$index])->multipliedBy($unit->conversionRatio)->toScale(2));
                }
            }
            app(ItemPriceCatalog::class)->saveForItem($id, $prices);
            $context['items'][$key] = $id;
        }

        return $context;
    }
}
