<?php

use App\Models\User;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Platform\Hardware\Database\Seeders\HardwareAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

$root = dirname(__DIR__, 3);
$parent = realpath($root.'/storage/framework/testing');
$qa = realpath(getenv('LARAVEL_STORAGE_PATH') ?: '');
if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'sqlite' || $qa === false || $parent === false
    || realpath(dirname($qa)) !== $parent || ! preg_match('/^hardware-label-[a-f0-9]+$/D', basename($qa))) {
    throw new LogicException('Root QA label tidak aman.');
}
foreach (['DB_DATABASE', 'APP_CONFIG_CACHE', 'APP_ROUTES_CACHE'] as $key) {
    $path = getenv($key);
    if (! is_string($path) || realpath(dirname($path)) !== $qa) {
        throw new LogicException('Database/cache wajib terisolasi sebelum bootstrap.');
    }
}
if (basename(getenv('DB_DATABASE')) !== 'database.sqlite') {
    throw new LogicException('Database QA tidak sesuai.');
}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(config('database.connections.sqlite.database')) !== realpath(getenv('DB_DATABASE'))
    || str_replace('\\', '/', storage_path()) !== str_replace('\\', '/', $qa)) {
    throw new LogicException('Bootstrap QA label tidak terisolasi.');
}
if (($argv[1] ?? '') === 'setup') {
    if (filesize(getenv('DB_DATABASE')) !== 0) {
        throw new LogicException('Setup hanya database QA kosong.');
    }
    $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
    if (! is_string($input['password'] ?? null) || strlen($input['password']) < 32) {
        throw new LogicException('Password QA wajib melalui stdin.');
    }
    Artisan::call('migrate', ['--force' => true]);
    app(UnitsDemoSeeder::class)->run();
    app(ItemsDemoSeeder::class)->run();
    app(HardwareAccessSeeder::class)->run();
    Permission::findOrCreate('inventory.view', 'web');
    $operator = User::create(['name' => 'Operator Label QA', 'email' => 'label.operator@example.test', 'password' => $input['password']]);
    $operator->givePermissionTo(['platform.hardware.view', 'platform.hardware.manage', 'platform.hardware.print', 'platform.hardware.reprint', 'inventory.view']);
    User::create(['name' => 'Pembaca Label QA', 'email' => 'label.viewer@example.test', 'password' => $input['password']])->givePermissionTo('platform.hardware.view');
    $camera = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->first();
    DB::table('item_units')->where('item_id', $camera->id)->where('unit_id', $camera->base_unit_id)->update(['barcode' => '001234567890']);
    $archived = strtolower((string) Str::ulid());
    $spec = ['width_mm' => 50, 'height_mm' => 30, 'dpi' => 203, 'orientation' => 'portrait'];
    DB::table('hardware_printers')->insert(['id' => $archived, 'name' => 'Arsip QA', 'printer_name' => 'ARSIP QA', 'gateway_host' => 'localhost',
        'specification' => json_encode($spec), 'is_active' => false, 'version' => 1, 'deleted_at' => now()->utc(), 'created_at' => now()->utc(), 'updated_at' => now()->utc()]);
    for ($i = 0; $i < 12; $i++) {
        DB::table('hardware_label_jobs')->insert(['id' => strtolower((string) Str::ulid()), 'printer_id' => $archived, 'created_by' => $operator->id,
            'idempotency_key' => (string) Str::uuid(), 'request_hash' => str_repeat('0', 64), 'kind' => 'test_label', 'status' => 'submitted',
            'snapshot' => json_encode(['name' => 'DEMO HISTORI QA', 'sku' => 'QA', 'unit_name' => 'Unit', 'barcode' => '001234567890']),
            'printer_snapshot' => json_encode(['id' => $archived, 'name' => 'Arsip QA', 'printer_name' => 'ARSIP QA', 'gateway_host' => 'localhost', ...$spec, 'version' => 1]),
            'copies' => 1, 'created_at' => now()->utc()->subDay(), 'updated_at' => now()->utc()->subDay()]);
    }
} elseif (($argv[1] ?? '') !== 'verify') {
    throw new LogicException('Mode QA tidak valid.');
}
$snapshot = [];
foreach (Schema::getTableListing(schemaQualified: false) as $table) {
    if (in_array($table, ['sessions', 'cache', 'cache_locks', 'hardware_printers', 'hardware_label_jobs', 'hardware_print_attempts'], true)) {
        continue;
    }
    $query = DB::table($table);
    if ($table === 'activity_log') {
        $query->where(fn ($q) => $q->where('log_name', '!=', 'platform.hardware')->orWhereNull('log_name'));
    }
    $rows = $query->get()->map(fn ($r) => json_encode($r, JSON_THROW_ON_ERROR))->all();
    sort($rows);
    $snapshot[$table] = hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
}
ksort($snapshot);
echo json_encode(['fingerprint' => hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR)), 'table_fingerprints' => $snapshot,
    'jobs' => DB::table('hardware_label_jobs')->count(), 'attempts' => DB::table('hardware_print_attempts')->count(),
    'statuses' => DB::table('hardware_label_jobs')->whereDate('created_at', now()->utc()->toDateString())->pluck('status')->all()], JSON_THROW_ON_ERROR);
