<?php

use App\Modules\Inventory\Items\Application\Actions\SaveItemCatalog;
use App\Modules\Inventory\Items\Application\Contracts\ItemPricingLookup;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceCatalog;
use App\Modules\Sales\Parties\Application\DTOs\ItemPriceData;
use App\Modules\Sales\Parties\Database\Seeders\ItemPricesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA hanya local MySQL dengan database disposable baru.');
}
$original = config('database.connections.mysql');
$name = 'pos_prices_qa_'.bin2hex(random_bytes(8));
if (! preg_match('/^pos_prices_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
    throw new LogicException('Nama database QA tidak aman.');
}
$server = DB::connection('mysql')->getPdo();
$created = false;
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException($label);
    }
    echo "PASS: {$label}\n";
};
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connection = array_replace($original, ['database' => $name]);
    config(['database.connections.prices_qa' => $connection, 'database.connections.prices_peer' => $connection, 'database.default' => 'prices_qa']);
    $app->detectEnvironment(fn () => 'testing');
    Artisan::call('migrate', ['--force' => true]);
    foreach ([UnitsDemoSeeder::class, ItemsDemoSeeder::class, PartiesDemoSeeder::class, ItemPricesDemoSeeder::class] as $seeder) {
        app($seeder)->run();
    }
    $snapshot = static fn () => DB::table('item_prices')->orderBy('id')->get()->toJson();
    $before = $snapshot();
    app(ItemPricesDemoSeeder::class)->run();
    $check(DB::table('item_prices')->count() === 54 && $before === $snapshot(), '54 harga relasional exact dan repeat idempotent');
    $id = app(ItemPricingLookup::class)->findIdBySku('DEMO-CCTV-IP-2MP');
    $catalog = app(ItemPriceCatalog::class);
    $prices = $catalog->forItems([$id])[$id];
    $prices[0] = new ItemPriceData($prices[0]->unitId, $prices[0]->customerLevelId, '999999999999999999.99');
    $catalog->saveForItem($id, $prices);
    $check(DB::table('item_prices')->where('item_id', $id)->where('unit_id', $prices[0]->unitId)
        ->where('customer_level_id', $prices[0]->customerLevelId)->value('amount') === '999999999999999999.99', 'DECIMAL(20,2) maksimum tersimpan exact tanpa float');
    $peer = DB::connection('prices_peer');
    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $peer->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $level = $prices[0]->customerLevelId;
    DB::beginTransaction();
    try {
        DB::table('customer_levels')->where('id', $level)->first();
        $peer->table('customer_levels')->where('id', $level)->update(['is_active' => false]);
        $rejected = false;
        try {
            $catalog->saveForItem($id, $prices);
        } catch (ValidationException) {
            $rejected = true;
        }
        $check($rejected, 'Current level lock menolak matrix stale setelah snapshot REPEATABLE READ');
    } finally {
        DB::rollBack();
    }
    $peer->table('customer_levels')->where('id', $level)->update(['is_active' => true]);
    DB::beginTransaction();
    try {
        DB::table('item_units')->where('item_id', $id)->get();
        $peer->table('item_units')->where('item_id', $id)->where('unit_id', $prices[0]->unitId)->update(['is_sale_unit' => false]);
        $rejected = false;
        try {
            $catalog->saveForItem($id, $prices);
        } catch (ValidationException) {
            $rejected = true;
        }
        $check($rejected, 'Current unit lock menolak flag satuan jual terbaru, bukan snapshot lama');
    } finally {
        DB::rollBack();
    }
    $peer->table('item_units')->where('item_id', $id)->where('unit_id', $prices[0]->unitId)->update(['is_sale_unit' => true]);
    $before = $snapshot();
    $item = DB::table('items')->where('id', $id)->first();
    $audit = DB::table('activity_log')->count();
    $rejected = false;
    try {
        app(SaveItemCatalog::class)->save(new CreateItemData($item->sku, 'Must rollback', $item->base_unit_id), [], $id);
    } catch (ValidationException) {
        $rejected = true;
    }
    $check($rejected && $before === $snapshot() && DB::table('items')->where('id', $id)->value('name') === $item->name
        && DB::table('activity_log')->count() === $audit, 'Harga invalid rollback perubahan katalog, relasi dan audit MySQL');
} finally {
    DB::purge('prices_peer');
    DB::purge('prices_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: database disposable dibersihkan; database pengguna tidak dimutasi.\n";
    }
}
