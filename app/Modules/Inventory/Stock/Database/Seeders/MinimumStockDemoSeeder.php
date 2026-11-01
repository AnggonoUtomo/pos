<?php

namespace App\Modules\Inventory\Stock\Database\Seeders;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Stock\Application\Actions\ManageMinimumStock;
use App\Modules\Inventory\Stock\Application\Contracts\MinimumStockRepository;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

final class MinimumStockDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo stok minimum hanya local/testing.');
        }
        $warehouse = collect(app(WarehouseLookup::class)->choices())->firstWhere('code', 'WH-MAIN')
            ?? throw new LogicException('Jalankan demo gudang, satuan dan barang dahulu.');
        $items = collect(app(ItemStockLookup::class)->choices())->keyBy('sku');
        DB::transaction(function () use ($warehouse, $items): void {
            app(WarehouseLookup::class)->findActive($warehouse->id, lock: true);
            foreach (['DEMO-CCTV-IP-2MP' => '4', 'DEMO-CCTV-UTP-CAT6' => '50', 'DEMO-ELEC-NYM-3X15' => '25.5'] as $sku => $minimum) {
                $item = $items[$sku] ?? throw new LogicException('Master demo CCTV/kelistrikan belum lengkap.');
                if (! app(MinimumStockRepository::class)->pairExists($warehouse->id, $item->id)) {
                    app(ManageMinimumStock::class)->create(['warehouse_id' => $warehouse->id, 'item_id' => $item->id,
                        'minimum_quantity' => $minimum, 'is_active' => true]);
                }
            }
        });
    }
}
