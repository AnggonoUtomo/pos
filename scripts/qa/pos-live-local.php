<?php

use App\Modules\Sales\POS\Application\DTOs\CartLineData;
use App\Modules\Sales\POS\Application\Queries\CheckCart;
use App\Modules\Sales\POS\Application\Queries\SaleCatalog;
use App\Modules\Sales\POS\Application\Queries\SaleContext;
use App\Modules\Sales\POS\Database\Seeders\POSAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Smoke POS hanya pada MySQL local, tanpa reset/seed transaksi.');
}
$snapshot = static function (): array {
    $hashes = [];
    foreach (['users', 'roles', 'model_has_roles', 'model_has_permissions', 'role_has_permissions',
        'warehouses', 'units', 'items', 'item_units', 'customers', 'customer_levels', 'item_prices',
        'stock_openings', 'stock_opening_lines', 'stock_balances', 'stock_movements', 'fifo_layers', 'activity_log'] as $table) {
        $rows = DB::table($table)->get()->map(fn ($row) => hash('sha256', json_encode($row, JSON_THROW_ON_ERROR)))->all();
        sort($rows);
        $hashes[$table] = hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
    }

    return $hashes;
};
$before = $snapshot();
app(POSAccessSeeder::class)->run();
$options = app(SaleContext::class)->options();
$warehouse = collect($options['warehouses'])->firstWhere('code', 'WH-MAIN');
$customer = collect($options['customers'])->firstWhere('code', 'DEMO-INSTALLER');
if ($warehouse === null || $customer === null) {
    throw new LogicException('Prasyarat demo lokal harus sudah tersedia; tidak di-seed otomatis.');
}
$catalog = app(SaleCatalog::class)->handle($warehouse->id, $customer->id, 'DEMO-CCTV-IP-2MP', 1);
$item = $catalog['items'][0] ?? null;
if ($item === null) {
    throw new LogicException('Demo barang lokal belum tersedia.');
}
$unit = collect($item['units'])->firstWhere('id', $item['base_unit_id']);
$quote = app(CheckCart::class)->handle($warehouse->id, $customer->id, [new CartLineData($item['id'], $unit['id'], '1')]);
if ($quote['lines'][0]['unit_price'] !== $unit['price'] || $before !== $snapshot()) {
    throw new RuntimeException('Harga tidak konsisten atau fingerprint berubah; hentikan delivery.');
}
echo "PASS: MySQL katalog/quote nyata memakai matrix pelanggan, quantity/uang exact.\n";
echo "PASS: fingerprint akun/grant/master/harga/stok/ledger/audit identik; tanpa transaksi/reservasi/demo ulang.\n";
