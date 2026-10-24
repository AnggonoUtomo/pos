<?php

namespace App\Modules\Inventory\Transfers\Database\Seeders;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Transfers\Application\Actions\ManageTransferDraft;
use App\Modules\Inventory\Transfers\Application\Contracts\TransferDraftRepository;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class TransfersDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo transfer hanya local/testing.');
        }
        DB::transaction(function (): void {
            $key = 'cctv-electrical-transfer-v1';
            if (app(TransferDraftRepository::class)->demoExists($key)) {
                return;
            }
            $warehouses = collect(app(WarehouseLookup::class)->choices());
            $source = $warehouses->firstWhere('code', 'WH-MAIN');
            $destination = $warehouses->firstWhere('code', 'WH-SECONDARY');
            if ($source === null || $destination === null) {
                return;
            }
            $items = collect(app(ItemStockLookup::class)->choices());
            $lines = [];
            foreach ([['DEMO-CCTV-IP-2MP', 'BOX', '1'], ['DEMO-ELEC-NYM-3X15', 'ROLL', '0.125']] as [$sku, $code, $quantity]) {
                $item = $items->firstWhere('sku', $sku);
                $unit = app(UnitLookup::class)->findActiveByCode($code);
                if ($item === null || $unit === null || ! collect($item->units)->contains(fn ($row) => $row->unitId === $unit->id)) {
                    return;
                }
                $lines[] = ['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => $quantity];
            }
            app(ManageTransferDraft::class)->save(['source_warehouse_id' => $source->id, 'destination_warehouse_id' => $destination->id,
                'notes' => 'Draft demo CCTV dan kelistrikan', 'lines' => $lines], demoKey: $key);
        }, 3);
    }
}
