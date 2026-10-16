<?php

use App\Models\User;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersAccessSeeder;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(dirname($database)) !== realpath(__DIR__.'/../../storage/framework/testing')
    || ! preg_match('/^suppliers-browser-[a-f0-9]{16}\.sqlite$/D', basename($database))) {
    throw new LogicException('QA Supplier hanya SQLite disposable.');
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (($input['verify'] ?? false) === true) {
    $row = DB::table('suppliers')->where('code', 'QA-CCTV')->first();
    $audit = DB::table('activity_log')->where('log_name', 'purchasing.suppliers')->get();
    if ($row === null || $row->name !== 'Supplier QA Updated' || $row->deleted_at !== null || $row->is_active
        || $row->email !== 'private-qa@example.test' || $audit->count() !== 7
        || str_contains($audit->toJson(), 'private-qa@example.test')) {
        throw new RuntimeException('Persistence/audit/privacy QA tidak sesuai.');
    }
    echo 'PASS: persistence, restore nonaktif dan audit redacted.';
    exit;
}
if (! is_string($input['password'] ?? null) || strlen($input['password']) < 24) {
    throw new LogicException('Credential QA ephemeral wajib via stdin.');
}
Artisan::call('migrate', ['--force' => true]);
app(SuppliersAccessSeeder::class)->run();
app(SuppliersDemoSeeder::class)->run();
foreach (['manager', 'viewer', 'outsider'] as $role) {
    $user = User::create(['name' => 'QA '.$role, 'email' => "qa-{$role}@example.test", 'password' => $input['password']]);
    if ($role === 'manager') {
        $user->givePermissionTo(['purchasing.suppliers.view', 'purchasing.suppliers.manage']);
    } elseif ($role === 'viewer') {
        $user->givePermissionTo('purchasing.suppliers.view');
    }
}
echo 'Fixture Supplier siap.';
