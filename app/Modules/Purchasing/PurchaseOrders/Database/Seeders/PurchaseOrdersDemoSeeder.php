<?php

namespace App\Modules\Purchasing\PurchaseOrders\Database\Seeders;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\PurchaseOrderRepository;
use App\Modules\Purchasing\Suppliers\Application\Contracts\SupplierLookup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class PurchaseOrdersDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo pembelian hanya local/testing.');
        }
        DB::transaction(function (): void {
            $key = 'cctv-electrical-purchase-v1';
            if (app(PurchaseOrderRepository::class)->demoExists($key)) {
                return;
            }
            $supplier = collect(app(SupplierLookup::class)->choices())->firstWhere('code', 'DEMO-SUP-CCTV')
                ?? throw new LogicException('Supplier demo CCTV harus tersedia dan aktif.');
            $warehouse = collect(app(WarehouseLookup::class)->choices())->firstWhere('code', 'WH-MAIN')
                ?? throw new LogicException('Gudang demo WH-MAIN harus aktif.');
            $items = collect(app(ItemStockLookup::class)->choices());
            $units = app(UnitLookup::class);
            $lines = [];
            foreach ([['DEMO-CCTV-IP-2MP', 'BOX', '2', '1800000'], ['DEMO-ELEC-NYM-3X15', 'ROLL', '0.125', '900000']] as [$sku, $code, $quantity, $cost]) {
                $item = $items->firstWhere('sku', $sku) ?? throw new LogicException('Barang demo CCTV/kelistrikan harus aktif.');
                $unit = $units->findActiveByCode($code) ?? throw new LogicException('Satuan demo pembelian harus aktif.');
                $lines[] = ['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => $quantity, 'unit_cost' => $cost];
            }
            app(ManagePurchaseOrder::class)->save(['supplier_id' => $supplier->id, 'warehouse_id' => $warehouse->id,
                'notes' => 'Draft demo CCTV dan kelistrikan', 'lines' => $lines], demoKey: $key);
        }, 3);
    }
}
