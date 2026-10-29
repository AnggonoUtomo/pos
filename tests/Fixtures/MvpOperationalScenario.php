<?php

namespace Tests\Fixtures;

use App\Models\User;
use App\Modules\Inventory\Stock\Application\Actions\PostAdjustment;
use App\Modules\Inventory\Transfers\Application\Actions\ManageTransferDraft;
use App\Modules\Inventory\Transfers\Application\Actions\PostTransfer;
use App\Modules\Platform\CompanySettings\Application\Actions\UpdateCompanySettings;
use App\Modules\Reporting\OperationalReports\Application\Queries\OperationalReport;
use App\Modules\Sales\Parties\Database\Seeders\ItemPricesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use App\Modules\Sales\POS\Application\Actions\PostInvoice;
use App\Modules\Sales\POS\Application\DTOs\CartLineData;
use App\Modules\Sales\POS\Application\Queries\CheckCart;
use App\Modules\Sales\Returns\Application\Actions\SettleReturnRefund;
use App\Modules\Sales\Returns\Application\Queries\QuoteReturnRefund;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class MvpOperationalScenario
{
    public static function run(): array
    {
        if (! app()->environment('testing')) {
            throw new \LogicException('Skenario hanya database QA/testing disposable.');
        }
        $actor = User::factory()->create();
        User::factory()->create();
        app(UpdateCompanySettings::class)->handle(['name' => 'Toko CCTV QA', 'version' => 0], $actor->id);
        $purchase = PurchaseReturnRefundFixture::purchase('10', '120000');
        CashierShiftFixture::open($actor->id);
        app(PartiesDemoSeeder::class)->run();
        app(ItemPricesDemoSeeder::class)->run();
        $item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->sole();
        DB::table('item_prices')->where('item_id', $item->id)->update(['amount' => '100000.00']);
        $warehouse = DB::table('warehouses')->where('code', 'WH-MAIN')->value('id');
        $destination = DB::table('warehouses')->where('code', 'WH-SECONDARY')->value('id');
        $customer = DB::table('customers')->where('code', 'DEMO-INSTALLER')->value('id');
        $method = DB::table('payment_methods')->where('code', 'TUNAI')->value('id');
        $pricing = ['discount_type' => 'percent', 'discount_value' => '10', 'tax_rate' => '11'];
        $quote = app(CheckCart::class)->handle($warehouse, $customer, [new CartLineData($item->id, $item->base_unit_id, '2')], pricing: $pricing);
        $body = ['idempotency_key' => strtolower((string) Str::ulid()), 'warehouse_id' => $warehouse, 'customer_id' => $customer,
            'quote_fingerprint' => $quote['fingerprint'], 'pricing' => $pricing,
            'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => '2']],
            'payments' => [['method_id' => $method, 'amount' => '200000']]];
        $invoice = app(PostInvoice::class)->handle($body, $actor->id);
        if ($invoice !== app(PostInvoice::class)->handle($body, $actor->id)) {
            throw new RuntimeException('Replay checkout berbeda.');
        }
        $return = ReturnRefundFixture::posted($invoice, '1');
        $refund = app(QuoteReturnRefund::class)->handle($return);
        $refundBody = ['version' => $refund['version'], 'quote_fingerprint' => $refund['quote_fingerprint'], 'payments' => [['method_id' => $method, 'amount' => $refund['total']]]];
        app(SettleReturnRefund::class)->handle($return, $refundBody, $actor->id);
        $purchaseReturn = PurchaseReturnRefundFixture::posted($purchase, '1');
        $receipt = app(\App\Modules\Purchasing\Returns\Application\Queries\QuoteReturnRefund::class)->handle($purchaseReturn);
        app(\App\Modules\Purchasing\Returns\Application\Actions\SettleReturnRefund::class)->handle($purchaseReturn,
            ['version' => $receipt['version'], 'quote_fingerprint' => $receipt['quote_fingerprint'],
                'payments' => [['method_id' => $method, 'amount' => $receipt['total']]]], $actor->id);
        $transfer = app(ManageTransferDraft::class)->save(['source_warehouse_id' => $warehouse, 'destination_warehouse_id' => $destination,
            'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => '2']]]);
        app(PostTransfer::class)->handle($transfer, ['version' => 1], $actor->id);
        app(PostTransfer::class)->handle($transfer, ['version' => 1], $actor->id);
        app(PostAdjustment::class)->handle(['idempotency_key' => strtolower((string) Str::ulid()), 'warehouse_id' => $destination,
            'item_id' => $item->id, 'direction' => 'out', 'quantity' => '0.5', 'reason' => 'Selisih hitung fisik QA'], $actor->id);
        $report = app(OperationalReport::class)->handle(['from' => now('Asia/Jakarta')->toDateString(), 'to' => now('Asia/Jakarta')->toDateString()]);
        $expected = ['sales' => ['total', '199800'], 'purchases' => ['total_value', '1200000'],
            'sales_returns' => ['settled_total', '99900'], 'purchase_returns' => ['settled_total', '120000'],
            'stock' => ['total_value', '900000'], 'payments' => ['net_movement', '-980100']];
        foreach ($expected as $section => [$key, $value]) {
            if (! BigDecimal::of($report[$section][$key])->isEqualTo($value)) {
                throw new RuntimeException('Rekonsiliasi skenario gagal: '.$section);
            }
        }
        if (DB::table('stock_movements')->count() !== 7 || DB::table('sales_invoices')->count() !== 1
            || $invoice['company_snapshot']['name'] !== 'Toko CCTV QA') {
            throw new RuntimeException('Snapshot/ledger skenario tidak sesuai.');
        }

        return ['status' => 'PASS', 'movements' => 7, 'invoice_total' => $invoice['total'],
            'customer_refund' => $refund['total'], 'supplier_refund' => $receipt['total'],
            'stock_value' => $report['stock']['total_value'], 'net_cash_movement' => $report['payments']['net_movement']];
    }
}
