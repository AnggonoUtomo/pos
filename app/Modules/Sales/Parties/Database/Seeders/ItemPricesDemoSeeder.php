<?php

namespace App\Modules\Sales\Parties\Database\Seeders;

use App\Modules\Inventory\Items\Application\Contracts\ItemPricingLookup;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceCatalog;
use App\Modules\Sales\Parties\Application\DTOs\ItemPriceData;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\ItemPrice;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class ItemPricesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo harga hanya boleh dijalankan pada local/testing.');
        }
        DB::transaction(function (): void {
            $catalog = app(ItemPriceCatalog::class);
            $items = app(ItemPricingLookup::class);
            $levels = $catalog->activeLevels(lock: true);
            $factors = ['ECERAN' => '1', 'GROSIR' => '0.9', 'INSTALLER' => '0.95'];
            if (count($levels) !== 3 || array_diff(array_column($levels, 'code'), array_keys($factors)) !== []) {
                throw new LogicException('Demo harga memerlukan tepat level aktif ECERAN, GROSIR, INSTALLER.');
            }
            foreach ([
                'DEMO-CCTV-IP-2MP' => '650000', 'DEMO-CCTV-NVR-8CH' => '1250000',
                'DEMO-CCTV-UTP-CAT6' => '5000', 'DEMO-ELEC-NYM-3X15' => '19000',
                'DEMO-ELEC-MCB-6A' => '45000', 'DEMO-CCTV-RJ45' => '2500',
                'DEMO-CCTV-ADAPTER-12V' => '65000', 'DEMO-CCTV-BALUN' => '15000',
                'DEMO-CCTV-KIT' => '3200000',
            ] as $sku => $baseAmount) {
                $id = $items->findIdBySku($sku);
                $item = $id === null ? null : $items->lock($id);
                if ($item === null) {
                    throw new LogicException("Barang {$sku} dan satuannya harus tersedia sebelum demo harga.");
                }
                // Any historical price means this item is user-owned, not demo-fillable.
                if (ItemPrice::withTrashed()->where('item_id', $id)->lockForUpdate()->first() !== null) {
                    continue;
                }
                $prices = [];
                foreach ($item->saleUnits as $unit) {
                    foreach ($levels as $level) {
                        $amount = BigDecimal::of($baseAmount)->multipliedBy($unit->conversionRatio)
                            ->multipliedBy($factors[$level->code])->toScale(2, RoundingMode::HALF_UP);
                        $prices[] = new ItemPriceData($unit->unitId, $level->id, (string) $amount);
                    }
                }
                $catalog->saveForItem($id, $prices);
            }
        }, 3);
    }
}
