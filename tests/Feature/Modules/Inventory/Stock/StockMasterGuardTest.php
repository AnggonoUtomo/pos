<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\CustomerLevel;

class StockMasterGuardTest extends StockTestCase
{
    public function test_posted_stock_blocks_destructive_item_and_warehouse_mutation(): void
    {
        $level = CustomerLevel::create(['code' => 'ECERAN', 'name' => 'Eceran'])->id;
        $id = $this->draft();
        $this->postJson("/inventory/stock/openings/{$id}/post")->assertOk();
        $this->deleteJson("/inventory/items/{$this->item}")->assertUnprocessable();
        $this->deleteJson("/inventory/warehouses/{$this->warehouse}")->assertUnprocessable();
        $this->putJson("/inventory/warehouses/{$this->warehouse}", ['code' => 'QA', 'name' => 'Gudang QA', 'is_active' => false])->assertUnprocessable();
        $payload = ['sku' => 'QA', 'name' => 'Rename aman', 'base_unit_id' => $this->base, 'is_active' => true, 'units' => [
            ['unit_id' => $this->base, 'conversion_ratio' => '1'], ['unit_id' => $this->box, 'conversion_ratio' => '24'],
        ]];
        $payload['prices'] = array_map(fn ($unit) => ['unit_id' => $unit, 'customer_level_id' => $level, 'amount' => '1000'], [$this->base, $this->box]);
        $this->putJson("/inventory/items/{$this->item}", $payload)->assertUnprocessable();
        $payload['units'][1]['conversion_ratio'] = '12';
        $this->putJson("/inventory/items/{$this->item}", $payload)->assertOk();
        $this->assertDatabaseHas('stock_opening_lines', ['opening_id' => $id, 'item_name' => 'Barang QA']);
        array_pop($payload['units']);
        $this->putJson("/inventory/items/{$this->item}", $payload)->assertUnprocessable();
        $payload['units'][] = ['unit_id' => $this->box, 'conversion_ratio' => '12'];
        $payload['is_active'] = false;
        $this->putJson("/inventory/items/{$this->item}", $payload)->assertUnprocessable();
    }
}
