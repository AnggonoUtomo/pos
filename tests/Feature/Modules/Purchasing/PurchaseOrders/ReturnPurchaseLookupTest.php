<?php

namespace Tests\Feature\Modules\Purchasing\PurchaseOrders;

use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PayPurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\ReturnPurchaseLookup;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public static function timezones(): array
    {
        return ['WIB' => ['Asia/Jakarta'], 'UTC' => ['UTC']];
    }

    private function useTimezone(string $timezone): void
    {
        $original = date_default_timezone_get();
        $this->beforeApplicationDestroyed(fn () => date_default_timezone_set($original));
        date_default_timezone_set($timezone);
        config(['app.timezone' => $timezone]);
        $this->travelTo(Carbon::parse('2026-10-30 02:15:16', $timezone));
    }

    #[DataProvider('timezones')]
    public function test_receipt_and_order_preserve_the_same_posting_time(string $timezone): void
    {
        $this->useTimezone($timezone);
        $id = $this->received();
        $order = DB::table('purchase_orders')->where('id', $id)->first();
        $receipt = DB::table('stock_receipts')->where('id', $order->stock_receipt_id)->first();

        $this->assertSame($receipt->posted_at, $order->posted_at);
        $this->assertNotNull(app(ReturnPurchaseLookup::class)->findEligible($id));
    }

    #[DataProvider('timezones')]
    public function test_paid_source_preserves_finance_time_and_is_eligible_for_refund(string $timezone): void
    {
        $this->useTimezone($timezone);
        $this->seed(PaymentsDemoSeeder::class);
        $id = $this->received();
        $method = DB::table('payment_methods')->where('code', 'TUNAI')->value('id');
        app(PayPurchaseOrder::class)->handle($id, 2, [['method_id' => $method, 'amount' => '240000']], $this->manager->id);
        $order = DB::table('purchase_orders')->where('id', $id)->first();
        $payment = DB::table('payment_disbursements')->where('id', $order->payment_disbursement_id)->first();

        $this->assertSame($payment->paid_at, $order->paid_at);
        $this->assertNotNull(app(ReturnPurchaseLookup::class)->refundBasis($id));

        DB::table('purchase_orders')->where('id', $id)->update(['paid_at' => Carbon::parse($order->paid_at)->addSecond()]);
        $this->assertNull(app(ReturnPurchaseLookup::class)->refundBasis($id));
    }

    public function test_mismatched_receipt_time_is_not_accepted(): void
    {
        $id = $this->received();
        $order = DB::table('purchase_orders')->where('id', $id)->first();
        DB::table('stock_receipts')->where('id', $order->stock_receipt_id)
            ->update(['posted_at' => Carbon::parse($order->posted_at)->addSecond()]);

        $this->assertNull(app(ReturnPurchaseLookup::class)->findEligible($id));
        $this->assertSame([], app(ReturnPurchaseLookup::class)->searchEligible(''));
    }

    public static function mismatchedReceiptFields(): array
    {
        return [
            'number' => ['stock_receipts', 'source_number', 'OTHER-PO'],
            'source' => ['stock_receipts', 'source_id', '00000000000000000000000000'],
            'line reference' => ['stock_receipt_lines', 'source_line_id', '00000000000000000000000000'],
            'value' => ['stock_receipt_lines', 'total_value', '239999.999999'],
        ];
    }

    #[DataProvider('mismatchedReceiptFields')]
    public function test_receipt_integrity_checks_remain_strict(string $table, string $field, string $value): void
    {
        $id = $this->received();
        $receiptId = DB::table('purchase_orders')->where('id', $id)->value('stock_receipt_id');
        DB::table($table)->where($table === 'stock_receipts' ? 'id' : 'receipt_id', $receiptId)->update([$field => $value]);

        $this->assertNull(app(ReturnPurchaseLookup::class)->findEligible($id));
        $this->assertSame([], app(ReturnPurchaseLookup::class)->searchEligible(''));
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
