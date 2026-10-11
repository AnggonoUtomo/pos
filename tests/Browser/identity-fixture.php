<?php

use App\Models\User;
use App\Modules\Platform\Identity\Database\Seeders\IdentityPermissionsSeeder;
use App\Modules\Platform\Identity\Database\Seeders\IdentityRolesDemoSeeder;
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
    || realpath(dirname($database)) !== $expected || ! str_starts_with(basename($database), 'identity-browser-')) {
    throw new LogicException('QA hanya boleh memakai SQLite disposable di storage/framework/testing.');
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (! is_string($input['password'] ?? null) || strlen($input['password']) < 24) {
    throw new LogicException('Credential QA ephemeral wajib tersedia melalui stdin.');
}
Artisan::call('migrate', ['--force' => true]);
foreach (['inventory.view', 'inventory.manage', 'sales.pos.invoices.create'] as $name) {
    Permission::findOrCreate($name, 'web');
}
app(IdentityPermissionsSeeder::class)->run();
app(IdentityRolesDemoSeeder::class)->run();
foreach (['manager' => 'QA Super System', 'viewer' => 'QA Viewer', 'staff' => 'QA Staff', 'delegate' => 'QA Delegate'] as $key => $name) {
    $user = User::create(['name' => $name, 'email' => "qa-{$key}@example.test", 'email_verified_at' => now(), 'password' => Hash::make($input['password'])]);
    if ($key === 'manager') {
        $user->assignRole(Role::findOrCreate('super-system', 'web'));
    } elseif ($key === 'viewer') {
        $user->givePermissionTo('platform.identity.view');
    } elseif ($key === 'delegate') {
        $user->givePermissionTo(['platform.identity.view', 'platform.identity.manage', 'inventory.view']);
    }
}
echo 'Fixture disposable siap.';
