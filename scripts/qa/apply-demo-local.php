<?php

use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Database\Seeders\StockDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Activation demo hanya pada MySQL local dengan instruksi user.');
}
$hash = static fn ($row): string => hash('sha256', json_encode($row, JSON_THROW_ON_ERROR));
$accounts = static function () use ($hash): array {
    $result = [];
    foreach (['users', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'] as $table) {
        $rows = DB::table($table)->get()->map($hash)->sort()->values()->all();
        $result[$table] = hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
    }

    return $result;
};
DB::transaction(function () use ($hash, $accounts): void {
    $beforeAccounts = $accounts();
    $existing = [];
    $counts = [];
    foreach (['units', 'warehouses', 'items', 'item_units', 'stock_openings', 'stock_opening_lines',
        'stock_balances', 'stock_movements', 'fifo_layers', 'customer_levels', 'customers', 'activity_log'] as $table) {
        $rows = DB::table($table)->get();
        $existing[$table] = $rows->mapWithKeys(fn ($row) => [$row->id => $hash($row)])->all();
        $counts[$table] = $rows->count();
    }
    foreach ([WarehousesDemoSeeder::class, UnitsDemoSeeder::class, ItemsDemoSeeder::class,
        PartiesDemoSeeder::class, StockDemoSeeder::class] as $seeder) {
        app($seeder)->run();
    }
    if ($accounts() !== $beforeAccounts) {
        throw new RuntimeException('Akun atau akses existing berubah; rollback demo.');
    }
    foreach ($existing as $table => $rows) {
        foreach ($rows as $id => $fingerprint) {
            if ($hash(DB::table($table)->where('id', $id)->first()) !== $fingerprint) {
                throw new RuntimeException('Row existing berubah; rollback demo tanpa reset.');
            }
        }
        echo $table.': +'.(DB::table($table)->count() - $counts[$table])."\n";
    }
});
echo "PASS: demo lokal tersedia; row existing, akun/password/role/grant dan history terjaga.\n";
