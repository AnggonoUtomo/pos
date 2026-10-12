<?php

use App\Modules\Inventory\Stock\Database\Seeders\StockAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Pemeriksaan ini hanya untuk MySQL local, bukan QA destructive.');
}
$snapshot = static function (): array {
    $hashes = [];
    foreach (['users', 'roles', 'model_has_roles', 'model_has_permissions', 'role_has_permissions', 'warehouses', 'units', 'items', 'item_units'] as $table) {
        $rows = DB::table($table)->get()->map(static fn ($row): string => hash('sha256', json_encode($row, JSON_THROW_ON_ERROR)))->all();
        sort($rows);
        $hashes[$table] = hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
    }

    return $hashes;
};
$before = $snapshot();
Artisan::call('migrate', ['--path' => 'app/Modules/Inventory/Stock/Database/Migrations', '--force' => true]);
echo Artisan::output();
app(StockAccessSeeder::class)->run();
if ($before !== $snapshot()) {
    throw new RuntimeException('Data existing berubah; hentikan delivery dan investigasi tanpa reset.');
}
foreach (['stock_openings', 'stock_opening_lines', 'stock_balances', 'stock_movements', 'fifo_layers'] as $table) {
    if (! Schema::hasTable($table)) {
        throw new RuntimeException('Schema Stock belum lengkap.');
    }
}
$columns = DB::select("select COLUMN_NAME as name, NUMERIC_PRECISION as precision_value, NUMERIC_SCALE as scale_value from information_schema.COLUMNS where TABLE_SCHEMA = database() and TABLE_NAME = 'stock_opening_lines' and COLUMN_NAME in ('base_quantity', 'total_value')");
foreach ($columns as $column) {
    $expected = $column->name === 'base_quantity' ? 20 : 30;
    if ((int) $column->precision_value !== $expected || (int) $column->scale_value !== 6) {
        throw new RuntimeException('Presisi MySQL tidak sesuai specification.');
    }
}
if (count($columns) !== 2) {
    throw new RuntimeException('Kolom valuasi MySQL belum lengkap.');
}
echo "PASS: migration Stock + permission-only seed; akun/credential/role/pivot/master identik, DECIMAL MySQL benar.\n";
