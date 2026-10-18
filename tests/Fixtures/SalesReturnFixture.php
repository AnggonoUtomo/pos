<?php

namespace Tests\Fixtures;

use App\Models\User;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Database\Seeders\StockDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\ItemPricesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use App\Modules\Sales\POS\Application\Actions\PostInvoice;
use App\Modules\Sales\POS\Application\DTOs\CartLineData;
use App\Modules\Sales\POS\Application\Queries\CheckCart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SalesReturnFixture
{
    public static function seed(): array
    {
        foreach ([WarehousesDemoSeeder::class, UnitsDemoSeeder::class, ItemsDemoSeeder::class,
            PartiesDemoSeeder::class, ItemPricesDemoSeeder::class, StockDemoSeeder::class, PaymentsDemoSeeder::class] as $seeder) {
            app($seeder)->run();
        }
        $actor = User::factory()->create();
        $item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->sole();
        $warehouse = DB::table('warehouses')->where('code', 'WH-MAIN')->value('id');
        $customer = DB::table('customers')->where('code', 'DEMO-INSTALLER')->value('id');
        $quote = app(CheckCart::class)->handle($warehouse, $customer, [new CartLineData($item->id, $item->base_unit_id, '2')]);

        return app(PostInvoice::class)->handle(['idempotency_key' => strtolower((string) Str::ulid()),
            'warehouse_id' => $warehouse, 'customer_id' => $customer, 'quote_fingerprint' => $quote['fingerprint'],
            'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => '2']],
            'payments' => [['method_id' => DB::table('payment_methods')->where('code', 'TUNAI')->value('id'), 'amount' => $quote['subtotal']]]], $actor->id);
    }

    public static function fingerprint(): string
    {
        $tables = ['sales_invoices', 'sales_invoice_lines', 'stock_balances', 'stock_movements', 'fifo_layers',
            'fifo_consumptions', 'stock_issues', 'stock_issue_lines', 'payment_receipts', 'payment_records'];

        return hash('sha256', json_encode(array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->all(), $tables), JSON_THROW_ON_ERROR));
    }
}
