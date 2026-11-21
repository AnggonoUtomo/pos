<?php

use App\Models\User;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

$root = dirname(__DIR__, 3);
$testing = realpath($root.'/storage/framework/testing');
$storage = getenv('LARAVEL_STORAGE_PATH');
$qa = is_string($storage) ? realpath($storage) : false;
if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'sqlite' || $qa === false
    || $testing === false || realpath(dirname($qa)) !== $testing
    || ! preg_match('/^hardware-scanner-[a-f0-9]+$/D', basename($qa))) {
    throw new LogicException('Fixture scanner wajib memakai root QA terisolasi.');
}
foreach (['DB_DATABASE', 'APP_CONFIG_CACHE', 'APP_ROUTES_CACHE'] as $key) {
    $value = getenv($key);
    if (! is_string($value) || realpath(dirname($value)) !== $qa) {
        throw new LogicException('Path fixture/cache scanner tidak aman.');
    }
}
if (basename(getenv('DB_DATABASE')) !== 'database.sqlite') {
    throw new LogicException('Database QA scanner tidak sesuai.');
}

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(config('database.connections.sqlite.database')) !== realpath(getenv('DB_DATABASE'))
    || str_replace('\\', '/', storage_path()) !== str_replace('\\', '/', $qa)) {
    throw new LogicException('Bootstrap QA scanner mengarah ke konfigurasi yang salah.');
}

if (($argv[1] ?? 'setup') === 'setup') {
    if (filesize(getenv('DB_DATABASE')) !== 0) {
        throw new LogicException('Setup hanya boleh memakai database QA kosong.');
    }
    $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
    if (! is_string($input['password'] ?? null) || strlen($input['password']) < 32) {
        throw new LogicException('Password QA ephemeral wajib melalui stdin.');
    }
    Artisan::call('migrate', ['--force' => true]);
    app(UnitsDemoSeeder::class)->run();
    app(ItemsDemoSeeder::class)->run();
    Permission::findOrCreate('inventory.view', 'web');
    $operator = User::create(['name' => 'Operator Uji Scanner', 'email' => 'scanner.operator@example.test', 'password' => $input['password']]);
    $operator->givePermissionTo('inventory.view');
    User::create(['name' => 'Pengguna Tanpa Hak Scanner', 'email' => 'scanner.denied@example.test', 'password' => $input['password']]);
    $camera = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->first();
    $mcb = DB::table('items')->where('sku', 'DEMO-ELEC-MCB-6A')->first();
    $box = DB::table('units')->where('code', 'BOX')->value('id');
    DB::table('item_units')->where('item_id', $camera->id)->where('unit_id', $camera->base_unit_id)->update(['barcode' => '0012345']);
    DB::table('item_units')->where('item_id', $camera->id)->where('unit_id', $box)->update(['barcode' => 'DEMO-CCTV-2MP-BOX']);
    DB::table('item_units')->where('item_id', $mcb->id)->where('unit_id', $mcb->base_unit_id)->update(['barcode' => 'DEMO-MCB-UNIT']);
    DB::table('item_units')->where('item_id', $mcb->id)->where('unit_id', $box)->update(['barcode' => 'PURCHASE-ONLY', 'is_sale_unit' => false]);
} elseif (($argv[1] ?? '') !== 'verify') {
    throw new LogicException('Mode fixture scanner tidak dikenal.');
}

$snapshot = [];
foreach (Schema::getTableListing(schemaQualified: false) as $table) {
    if (in_array($table, ['cache', 'cache_locks', 'sessions'], true)) {
        continue;
    }
    $rows = DB::table($table)->get()->map(fn ($row) => json_encode($row, JSON_THROW_ON_ERROR))->all();
    sort($rows);
    $snapshot[$table] = $rows;
}
ksort($snapshot);
$tableFingerprints = array_map(fn ($rows) => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR)), $snapshot);
echo json_encode(['fingerprint' => hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR)),
    'table_fingerprints' => $tableFingerprints,
    'table_count' => count($snapshot), 'hardware_enabled' => config('pos_hardware.enabled')], JSON_THROW_ON_ERROR);
