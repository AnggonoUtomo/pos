<?php

use App\Models\User;
use App\Modules\Inventory\Items\Database\Seeders\ItemsAccessSeeder;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Database\Seeders\StockAccessSeeder;
use App\Modules\Inventory\Stock\Database\Seeders\StockDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\ItemPricesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesAccessSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use App\Modules\Sales\POS\Database\Seeders\POSAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(dirname($database)) !== realpath(__DIR__.'/../../storage/framework/testing')
    || ! preg_match('/^parties-browser-[a-f0-9]{16}\.sqlite$/D', basename($database))) {
    throw new LogicException('QA pelanggan hanya menggunakan SQLite disposable.');
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (! is_string($input['password'] ?? null) || strlen($input['password']) < 24) {
    throw new LogicException('Credential ephemeral wajib melalui stdin.');
}
Artisan::call('migrate', ['--force' => true]);
foreach ([PartiesAccessSeeder::class, PartiesDemoSeeder::class, ItemsAccessSeeder::class, UnitsDemoSeeder::class] as $seeder) {
    app($seeder)->run();
}
if (($input['inventory_demo'] ?? false) === true) {
    foreach ([WarehousesDemoSeeder::class, ItemsDemoSeeder::class, StockAccessSeeder::class, StockDemoSeeder::class] as $seeder) {
        app($seeder)->run();
    }
    app(ItemPricesDemoSeeder::class)->run();
    app(POSAccessSeeder::class)->run();
    $cashier = User::create(['name' => 'QA cashier', 'email' => 'qa-cashier@example.test', 'password' => $input['password']]);
    $cashier->givePermissionTo('sales.pos.invoices.create');
}
foreach (['manager', 'viewer', 'outsider'] as $role) {
    $user = User::create(['name' => 'QA '.$role, 'email' => "qa-{$role}@example.test", 'password' => $input['password']]);
    if ($role === 'manager') {
        $user->assignRole(Role::findOrCreate('super-system', 'web'));
    }
    if ($role === 'viewer') {
        $user->givePermissionTo('sales.parties.view');
    }
}
echo 'Fixture pelanggan siap.';
