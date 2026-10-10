<?php

use App\Models\User;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$expected = realpath(__DIR__.'/../../storage/framework/testing');
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(dirname($database)) !== $expected || ! str_starts_with(basename($database), 'inventory-browser-')) {
    throw new LogicException('QA hanya boleh memakai SQLite disposable di storage/framework/testing.');
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (! is_string($input['password'] ?? null) || strlen($input['password']) < 24) {
    throw new LogicException('Credential QA ephemeral wajib tersedia melalui stdin.');
}
Artisan::call('migrate', ['--force' => true]);
foreach (['inventory.view', 'inventory.manage'] as $name) {
    Permission::findOrCreate($name, 'web');
}
$super = User::create(['name' => 'QA Super System', 'email' => 'qa-manager@example.test', 'email_verified_at' => now(), 'password' => Hash::make($input['password'])]);
$super->assignRole(Role::findOrCreate('super-system', 'web'));
$viewer = User::create(['name' => 'QA Viewer', 'email' => 'qa-viewer@example.test', 'email_verified_at' => now(), 'password' => Hash::make($input['password'])]);
$viewer->givePermissionTo('inventory.view');
app(WarehousesDemoSeeder::class)->run();
app(UnitsDemoSeeder::class)->run();
app(ItemsDemoSeeder::class)->run();
echo 'Fixture disposable siap.';
