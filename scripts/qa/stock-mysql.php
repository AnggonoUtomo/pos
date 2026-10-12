<?php

use App\Modules\Inventory\Items\Application\Actions\CreateItem;
use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Application\Actions\ManageOpening;
use App\Modules\Inventory\Stock\Application\Actions\PostOpening;
use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA MySQL hanya dari local dengan database disposable baru.');
}
$original = config('database.connections.mysql');
$name = 'pos_stock_qa_'.bin2hex(random_bytes(8));
if (! preg_match('/^pos_stock_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
    throw new LogicException('Nama database QA tidak aman.');
}
$server = DB::connection('mysql')->getPdo();
$check = static function (bool $condition, string $label): void {
    if (! $condition) {
        throw new RuntimeException($label);
    }
    echo 'PASS: '.$label."\n";
};
$created = false;
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connection = array_replace($original, ['database' => $name]);
    config(['database.connections.stock_qa' => $connection, 'database.connections.stock_qa_peer' => $connection, 'database.default' => 'stock_qa']);
    $app->detectEnvironment(fn () => 'testing');
    Artisan::call('migrate', ['--force' => true]);
    foreach ([WarehousesDemoSeeder::class, UnitsDemoSeeder::class, ItemsDemoSeeder::class] as $seeder) {
        app($seeder)->run();
    }
    $warehouse = collect(app(WarehouseLookup::class)->choices())->firstWhere('code', 'WH-MAIN');
    $item = collect(app(ItemStockLookup::class)->choices())->firstWhere('sku', 'DEMO-WATER-600');
    $box = app(UnitLookup::class)->findActiveByCode('BOX');
    $input = ['warehouse_id' => $warehouse->id, 'lines' => [['item_id' => $item->id, 'unit_id' => $box->id, 'quantity' => '2', 'unit_cost' => '120000']]];
    $id = app(ManageOpening::class)->create($input);
    $peer = DB::connection('stock_qa_peer');
    $peer->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $changed = false;
    $locks = [];
    DB::listen(function ($query) use ($id, $peer, $item, $box, &$changed, &$locks): void {
        if ($query->connectionName !== 'stock_qa') {
            return;
        }
        $sql = strtolower($query->sql);
        if (str_contains($sql, 'from `units`') && str_contains($sql, 'for update')) {
            foreach ($query->bindings as $binding) {
                if (is_string($binding) && preg_match('/^[0-9a-hjkmnp-tv-z]{26}$/iD', $binding)) {
                    $locks[] = strtolower($binding);
                    break;
                }
            }
        }
        // Interleave a committed editor change after the draft's consistent read.
        if (! $changed && str_contains($sql, 'from `stock_opening_lines`') && in_array($id, $query->bindings, true)) {
            $changed = true;
            $peer->transaction(function () use ($peer, $item, $box): void {
                $units = [$item->baseUnitId, $box->id];
                sort($units);
                foreach ($units as $unitId) {
                    $peer->table('units')->where('id', $unitId)->lockForUpdate()->first();
                }
                $peer->table('items')->where('id', $item->id)->lockForUpdate()->first();
                $peer->table('item_units')->where('item_id', $item->id)->where('unit_id', $box->id)->update(['conversion_ratio' => '24']);
            });
        }
    });
    $rejected = false;
    try {
        app(PostOpening::class)->handle($id);
    } catch (InvalidArgumentException $exception) {
        $rejected = str_contains($exception->getMessage(), 'Konversi draft berubah');
    }
    $check($changed && $rejected && DB::table('stock_balances')->count() === 0, 'Dua koneksi REPEATABLE READ: rasio stale ditolak, ledger kosong');
    $expected = [$item->baseUnitId, $box->id];
    sort($expected);
    $check(array_slice($locks, 0, 2) === $expected, 'Satuan dasar dan pilihan dikunci dalam urutan canonical');
    app(ManageOpening::class)->update($id, $input);
    app(PostOpening::class)->handle($id);
    app(PostOpening::class)->handle($id);
    $check(DB::table('stock_balances')->value('quantity') === '48.000000' && DB::table('fifo_layers')->count() === 1, 'Draft disimpan ulang: posting/retry exact dan idempotent');
    $second = app(ManageOpening::class)->create($input);
    $rejected = false;
    try {
        app(PostOpening::class)->handle($second);
    } catch (InvalidArgumentException) {
        $rejected = true;
    }
    $check($rejected && DB::table('stock_balances')->count() === 1, 'Stok awal kedua tidak menggandakan saldo MySQL');
    $large = app(CreateItem::class)->handle(new CreateItemData('QA-LARGE', 'Presisi QA', $item->baseUnitId));
    $input['lines'] = [['item_id' => $large, 'unit_id' => $item->baseUnitId, 'quantity' => '99999999999999.999999', 'unit_cost' => '1']];
    $largeId = app(ManageOpening::class)->create($input);
    app(PostOpening::class)->handle($largeId);
    $record = DB::transaction(fn () => app(StockRepository::class)->lockDraft($largeId));
    $check($record['lines'][0]['base_quantity'] === '99999999999999.999999'
        && DB::table('stock_balances')->where('item_id', $large)->value('quantity') === '99999999999999.999999', 'DECIMAL MySQL mempertahankan digit batas maksimum');
} finally {
    DB::purge('stock_qa_peer');
    DB::purge('stock_qa');
    if ($created) {
        // Only this generated, validated name may be dropped; never the application's database.
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: database disposable dibersihkan; database aplikasi tidak dimutasi.\n";
    }
}
