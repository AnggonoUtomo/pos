<?php

namespace App\Modules\Inventory\Stock\Database\Seeders;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Stock\Application\Actions\ManageOpening;
use App\Modules\Inventory\Stock\Application\Actions\PostOpening;
use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use App\Modules\Inventory\Stock\Infrastructure\Persistence\Models\StockOpening;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class StockDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo stok hanya boleh dijalankan pada local/testing.');
        }
        $marker = 'DEMO: opening-stock-v1';
        if (StockOpening::withTrashed()->where('notes', $marker)->exists()) {
            return;
        }
        $warehouse = collect(app(WarehouseLookup::class)->choices())->firstWhere('code', 'WH-MAIN');
        if ($warehouse === null) {
            throw new LogicException('Jalankan master demo gudang, satuan dan barang terlebih dahulu.');
        }
        $items = collect(app(ItemStockLookup::class)->choices())->keyBy('sku');
        $lines = [];
        foreach ([['DEMO-WATER-600', 'BOX', '2', '120000'], ['DEMO-TEA-250', 'PACK', '3', '18000'], ['DEMO-RICE', 'KG', '5', '16000']] as [$sku, $code, $quantity, $cost]) {
            $item = $items[$sku] ?? null;
            $unit = app(UnitLookup::class)->findActiveByCode($code);
            if ($item === null || $unit === null) {
                throw new LogicException('Master demo barang/satuan harus aktif dan tersedia.');
            }
            $lines[] = ['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => $quantity, 'unit_cost' => $cost];
        }
        DB::transaction(function () use ($warehouse, $lines, $marker): void {
            app(WarehouseLookup::class)->findActive($warehouse->id, lock: true);
            foreach ($lines as $line) {
                if (app(StockRepository::class)->hasBalance($warehouse->id, $line['item_id'])) {
                    return;
                }
            }
            $id = app(ManageOpening::class)->create(['warehouse_id' => $warehouse->id, 'notes' => $marker, 'lines' => $lines]);
            app(PostOpening::class)->handle($id);
        });
    }
}
