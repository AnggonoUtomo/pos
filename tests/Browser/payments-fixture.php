<?php

use App\Models\User;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsAccessSeeder;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(dirname($database)) !== realpath(__DIR__.'/../../storage/framework/testing')
    || ! preg_match('/^payments-browser-[a-f0-9]{16}\.sqlite$/D', basename($database))) {
    throw new LogicException('QA Finance hanya SQLite disposable.');
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (! is_string($input['password'] ?? null) || strlen($input['password']) < 24) {
    throw new LogicException('Credential QA ephemeral wajib via stdin.');
}
Artisan::call('migrate', ['--force' => true]);
app(PaymentsAccessSeeder::class)->run();
app(PaymentsDemoSeeder::class)->run();
foreach (['manager', 'viewer', 'outsider'] as $role) {
    $user = User::create(['name' => 'QA '.$role, 'email' => "qa-{$role}@example.test", 'password' => $input['password']]);
    if ($role === 'manager') {
        $user->givePermissionTo(['finance.payments.view', 'finance.payments.manage']);
    }
    if ($role === 'viewer') {
        $user->givePermissionTo('finance.payments.view');
    }
}
echo 'Fixture Finance siap.';
