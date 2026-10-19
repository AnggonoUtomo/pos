<?php

namespace Tests\Feature\Modules\Purchasing\PurchaseOrders;

use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\ReturnPurchaseLookup;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class ReturnPurchaseLookupTest extends StockTestCase
{
    private string $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supplier = Supplier::create(['code' => 'LOOKUP', 'name' => 'Supplier 100%_!', 'phone' => 'private-contact', 'is_active' => true])->id;
    }

    private function received(): string
    {
        $id = app(ManagePurchaseOrder::class)->save($this->payload() + ['supplier_id' => $this->supplier]);
        app(PostPurchaseOrder::class)->handle($id, 1, $this->manager->id);

        return $id;
    }

    public function test_published_projection_is_exact_safe_snapshot_and_search_is_literal(): void
    {
        $id = $this->received();
        DB::table('suppliers')->where('id', $this->supplier)->update(['name' => 'Renamed supplier', 'phone' => 'new-private-contact']);
        DB::table('items')->where('id', $this->item)->update(['name' => 'Renamed item']);
        DB::table('units')->where('id', $this->box)->update(['name' => 'Renamed unit']);
        $lookup = app(ReturnPurchaseLookup::class);
        $purchase = $lookup->findEligible(strtoupper($id));

        $this->assertNotNull($purchase);
        $data = $purchase->toArray();
        $this->assertSame(['id', 'number', 'supplier_id', 'supplier_code', 'supplier_name', 'warehouse_id', 'warehouse_code', 'warehouse_name', 'posted_at', 'lines'], array_keys($data));
        $this->assertSame(['id', 'item_id', 'unit_id', 'base_unit_id', 'sku', 'item_name', 'unit_name', 'base_unit_name', 'quantity', 'base_quantity', 'conversion_ratio', 'unit_cost', 'total_value'], array_keys($data['lines'][0]));
        $this->assertSame('Supplier 100%_!', $data['supplier_name']);
        $this->assertSame('Barang QA', $data['lines'][0]['item_name']);
        $this->assertSame('Dus', $data['lines'][0]['unit_name']);
        $this->assertSame('2.000000', $data['lines'][0]['quantity']);
        $this->assertSame('12.000000', $data['lines'][0]['conversion_ratio']);
        $this->assertSame('120000.000000', $data['lines'][0]['unit_cost']);
        $this->assertStringNotContainsString('private-contact', json_encode($data, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('payment_snapshot', json_encode($data, JSON_THROW_ON_ERROR));
        $this->assertSame([$id], array_map(fn ($row) => $row->header['id'], $lookup->searchEligible('%_!')));
        $this->expectException(InvalidArgumentException::class);
        $lookup->searchEligible(str_repeat('x', 151));
    }

    public function test_only_posted_orders_with_matching_receipt_metadata_are_eligible(): void
    {
        $draft = app(ManagePurchaseOrder::class)->save($this->payload() + ['supplier_id' => $this->supplier]);
        $valid = $this->received();
        $invalid = $this->received();
        $invalidLine = $this->received();
        $receipt = DB::table('purchase_orders')->where('id', $invalid)->value('stock_receipt_id');
        DB::table('stock_receipts')->where('id', $receipt)->update(['source_type' => 'sale']);
        $invalidReceipt = DB::table('purchase_orders')->where('id', $invalidLine)->value('stock_receipt_id');
        DB::table('stock_receipt_lines')->where('receipt_id', $invalidReceipt)->update(['quantity' => '23.000000']);

        $this->assertNull(app(ReturnPurchaseLookup::class)->findEligible($draft));
        $this->assertNull(app(ReturnPurchaseLookup::class)->findEligible($invalid));
        $this->assertNull(app(ReturnPurchaseLookup::class)->findEligible($invalidLine));
        $this->assertSame([$valid], array_map(fn ($row) => $row->header['id'], app(ReturnPurchaseLookup::class)->searchEligible('')));

        DB::table('purchase_orders')->where('id', $valid)->update(['deleted_at' => now()]);
        $this->assertSame([], app(ReturnPurchaseLookup::class)->searchEligible(''));
    }

    public function test_search_is_bounded_to_twenty_five_and_empty_query_does_not_leak_unposted(): void
    {
        $draft = app(ManagePurchaseOrder::class)->save($this->payload() + ['supplier_id' => $this->supplier]);
        $ids = [];
        for ($i = 0; $i < 26; $i++) {
            $ids[] = $this->received();
        }

        $results = app(ReturnPurchaseLookup::class)->searchEligible('');
        $this->assertCount(25, $results);
        $this->assertCount(25, array_unique(array_map(fn ($row) => $row->header['id'], $results)));
        $this->assertNotContains($draft, array_map(fn ($row) => $row->header['id'], $results));
    }
}
