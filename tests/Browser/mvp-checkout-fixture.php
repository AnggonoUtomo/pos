<?php

use App\Models\User;
use App\Modules\Platform\CompanySettings\Database\Seeders\CompanySettingsAccessSeeder;
use App\Modules\Platform\OperationalSettings\Database\Seeders\OperationalSettingsAccessSeeder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersAccessSeeder;
use App\Modules\Reporting\OperationalReports\Database\Seeders\OperationalReportsAccessSeeder;
use App\Modules\Sales\Parties\Database\Seeders\ItemPricesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use App\Modules\Sales\POS\Database\Seeders\POSAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\PurchaseReturnRefundFixture;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL fixture MVP: '.class_basename($error).' pada '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(dirname($database)) !== realpath(storage_path('framework/testing'))
    || ! preg_match('/^mvp-browser-[a-f0-9]{16}\.sqlite$/D', basename($database))) {
    throw new LogicException('Fixture hanya SQLite disposable.');
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (($input['verify_navigation'] ?? false) === true) {
    if (DB::table('stock_movements')->exists() || DB::table('sales_invoices')->exists()
        || DB::table('purchase_orders')->exists() || DB::table('operational_backup_runs')->exists()
        || (int) DB::table('operational_settings')->value('version') !== 0) {
        throw new RuntimeException('Navigasi tidak boleh mengubah data bisnis/pengaturan.');
    }
    echo json_encode(['status' => 'PASS', 'no_business_mutations' => true]);
    exit;
}
if (($input['verify_operational'] ?? false) === true) {
    if (DB::table('operational_settings')->value('receipt_paper') !== '80mm'
        || (bool) DB::table('operational_settings')->value('backup_enabled') !== false
        || DB::table('operational_settings')->value('backup_time') !== '22:15'
        || DB::table('operational_backup_runs')->exists() || DB::table('stock_movements')->exists()
        || DB::table('sales_invoices')->exists() || DB::table('purchase_orders')->exists()) {
        throw new RuntimeException('Pengaturan UI atau guard non-transaksional tidak sesuai.');
    }
    echo json_encode(['status' => 'PASS', 'no_transactions' => true, 'automatic_backup_disabled' => true]);
    exit;
}
if (($input['verify'] ?? false) === true) {
    $invoice = DB::table('sales_invoices')->sole();
    if ((string) $invoice->total !== '199800.00' || DB::table('payment_disbursements')->count() !== 2
        || DB::table('purchase_orders')->whereNull('payment_disbursement_id')->exists()
        || json_decode($invoice->company_snapshot, true)['name'] !== 'Toko CCTV & Listrik QA'
        || json_decode($invoice->pricing, true)['tax_rate'] !== '11.00') {
        throw new RuntimeException('Snapshot atau payment browser tidak sesuai.');
    }
    echo json_encode(['status' => 'PASS', 'invoices' => 1, 'paid_purchases' => 2, 'invoice_total' => '199800.00']);
    exit;
}
if (! is_string($input['password'] ?? null) || strlen($input['password']) < 24) {
    throw new LogicException('Credential random melalui stdin wajib.');
}
// This database is disposable: avoid filesystem sync overhead during fixture setup.
DB::statement('PRAGMA journal_mode=MEMORY');
DB::statement('PRAGMA synchronous=OFF');
Artisan::call('migrate', ['--force' => true]);
$manager = User::create(['name' => 'QA manager', 'email' => 'qa-manager@example.test', 'password' => $input['password'], 'email_verified_at' => now()]);
if (($input['operational_only'] ?? false) === true) {
    app(OperationalSettingsAccessSeeder::class)->run();
    $manager->assignRole(Role::findOrCreate('super-system', 'web'));
    echo json_encode(['status' => 'READY']);
    exit;
}
User::factory()->create();
PurchaseReturnRefundFixture::purchase('10', '120000');
foreach ([PartiesDemoSeeder::class, ItemPricesDemoSeeder::class, POSAccessSeeder::class, PurchaseOrdersAccessSeeder::class,
    CompanySettingsAccessSeeder::class, OperationalReportsAccessSeeder::class] as $seeder) {
    app($seeder)->run();
}
$manager->givePermissionTo(Permission::all());
$item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->sole();
DB::table('item_prices')->where('item_id', $item->id)->update(['amount' => '100000.00']);
$purchase = app(ManagePurchaseOrder::class)->save(['warehouse_id' => DB::table('warehouses')->where('code', 'WH-MAIN')->value('id'),
    'supplier_id' => DB::table('suppliers')->where('code', 'DEMO-SUP-CCTV')->value('id'),
    'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => '1', 'unit_cost' => '120000']]]);
echo json_encode(['status' => 'READY', 'purchase_number' => DB::table('purchase_orders')->where('id', $purchase)->value('number')]);
