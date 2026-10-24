<?php

namespace Tests\Feature\Modules\Inventory\Transfers;

use App\Modules\Inventory\Transfers\Database\Seeders\TransfersAccessSeeder;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

abstract class TransferTestCase extends StockTestCase
{
    protected string $destination;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TransfersAccessSeeder::class);
        $this->manager->givePermissionTo(['inventory.transfers.view', 'inventory.transfers.manage']);
        $this->destination = Warehouse::create(['code' => 'DST', 'name' => 'Gudang Tujuan', 'is_active' => true])->id;
    }

    protected function transfer(): array
    {
        return ['source_warehouse_id' => $this->warehouse, 'destination_warehouse_id' => $this->destination,
            'notes' => 'Catatan internal', 'lines' => [['item_id' => $this->item, 'unit_id' => $this->box, 'quantity' => '1.5']]];
    }
}
