<?php

use App\Models\User;
use App\Modules\Inventory\Stock\Application\Actions\ManageOpening;
use App\Modules\Inventory\Stock\Application\Actions\PostOpening;
use App\Modules\Sales\POS\Application\Actions\PostInvoice;
use App\Modules\Sales\POS\Application\DTOs\CartLineData;
use App\Modules\Sales\POS\Application\Queries\CheckCart;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(dirname($database)) !== realpath(__DIR__.'/../../storage/framework/testing')
    || ! preg_match('/^parties-browser-[a-f0-9]{16}\.sqlite$/D', basename($database))) {
    throw new LogicException('QA riwayat hanya SQLite disposable.');
}
$result = [];
if (($argv[1] ?? null) === '--seed') {
    $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
    if (! is_string($input['password'] ?? null) || strlen($input['password']) < 24 || DB::table('sales_invoices')->exists()) {
        throw new LogicException('Fixture riwayat wajib fresh dan credential ephemeral via stdin.');
    }
    $owner = User::where('email', 'qa-checkout@example.test')->sole();
    $other = User::create(['name' => 'QA other', 'email' => 'qa-other@example.test', 'password' => $input['password']]);
    $other->givePermissionTo('sales.pos.invoices.post');
    User::where('email', 'qa-viewer@example.test')->sole()->givePermissionTo('sales.pos.invoices.view');
    $item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->sole();
    $main = DB::table('warehouses')->where('code', 'WH-MAIN')->value('id');
    $secondary = DB::table('warehouses')->where('code', 'WH-SECONDARY')->value('id');
    $cash = DB::table('payment_methods')->where('code', 'TUNAI')->value('id');
    $opening = app(ManageOpening::class)->create(['warehouse_id' => $secondary, 'notes' => 'QA history secondary',
        'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => '1', 'unit_cost' => '40000']]]);
    app(PostOpening::class)->handle($opening);
    $sale = static function (User $actor, string $warehouse, string $code, string $date) use ($item, $cash): array {
        $customer = DB::table('customers')->where('code', $code)->value('id');
        $quote = app(CheckCart::class)->handle($warehouse, $customer, [new CartLineData($item->id, $item->base_unit_id, '0.1')]);
        $invoice = app(PostInvoice::class)->handle(['idempotency_key' => strtolower((string) Str::ulid()), 'warehouse_id' => $warehouse,
            'customer_id' => $customer, 'quote_fingerprint' => $quote['fingerprint'],
            'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => '0.1']],
            'payments' => [['method_id' => $cash, 'amount' => $quote['subtotal']]]], $actor->id);
        DB::table('sales_invoices')->where('id', $invoice['id'])->update(['posted_at' => $date]);

        return ['id' => $invoice['id'], 'number' => $invoice['number']];
    };
    $result['first'] = $sale($owner, $main, 'DEMO-UMUM', '2026-10-15 17:00:00');
    $result['lastDay'] = $sale($owner, $main, 'DEMO-INSTALLER', '2026-10-16 16:59:59');
    for ($i = 0; $i < 25; $i++) {
        $result['latest'] = $sale($owner, $main, 'DEMO-INSTALLER', '2026-10-18 00:00:00');
    }
    $result['other'] = $sale($other, $secondary, 'DEMO-GROSIR', '2026-10-16 17:00:00');
    DB::table('customers')->where('code', 'DEMO-INSTALLER')->update(['name' => 'Master changed', 'is_active' => false, 'deleted_at' => now()]);
    DB::table('warehouses')->where('id', $main)->update(['name' => 'Warehouse changed', 'is_active' => false, 'deleted_at' => now()]);
}
$hashes = [];
foreach (['sales_invoices', 'sales_invoice_lines', 'stock_issues', 'stock_issue_lines', 'stock_balances', 'stock_movements',
    'fifo_layers', 'fifo_consumptions', 'payment_receipts', 'payment_records', 'activity_log'] as $table) {
    $hashes[$table] = hash('sha256', DB::table($table)->orderBy('id')->get()->toJson());
}
$result['fingerprint'] = hash('sha256', json_encode($hashes, JSON_THROW_ON_ERROR));
$result['invoices'] = DB::table('sales_invoices')->count();
echo json_encode($result, JSON_THROW_ON_ERROR);
