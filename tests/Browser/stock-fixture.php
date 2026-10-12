<?php

use App\Models\User;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Database\Seeders\StockAccessSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(dirname($database)) !== realpath(__DIR__.'/../../storage/framework/testing')
    || ! str_starts_with(basename($database), 'stock-browser-')) {
    throw new LogicException('QA stok hanya menggunakan SQLite disposable.');
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (! is_string($input['password'] ?? null) || strlen($input['password']) < 24) {
    throw new LogicException('Credential ephemeral wajib melalui stdin.');
}
Artisan::call('migrate', ['--force' => true]);
app(StockAccessSeeder::class)->run();
foreach ([WarehousesDemoSeeder::class, UnitsDemoSeeder::class, ItemsDemoSeeder::class] as $seeder) {
    app($seeder)->run();
}
foreach (['manager', 'viewer', 'editor'] as $role) {
    $user = User::create(['name' => 'QA '.$role, 'email' => "qa-{$role}@example.test", 'password' => $input['password']]);
    if ($role === 'manager') {
        $user->assignRole(Role::findOrCreate('super-system', 'web'));
    } else {
        $user->givePermissionTo($role === 'viewer' ? ['inventory.stock.view'] : ['inventory.stock.view', 'inventory.stock.manage']);
    }
}
echo 'Fixture stok siap.';
