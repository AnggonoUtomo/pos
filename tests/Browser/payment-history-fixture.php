<?php

use App\Models\User;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Tests\Fixtures\PaymentHistoryFixture;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite' || realpath(dirname($database)) !== realpath(__DIR__.'/../../storage/framework/testing')
    || ! preg_match('/^payment-history-browser-[a-f0-9]{16}\.sqlite$/D', basename($database))) {
    throw new LogicException('QA hanya database SQLite disposable.');
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (isset($input['verify'])) {
    if (! is_string($input['verify']) || ! hash_equals($input['verify'], PaymentHistoryFixture::fingerprint())) {
        throw new RuntimeException('GET mengubah data bisnis.');
    }
    echo 'PASS: seluruh data bisnis/ledger/audit identik.';
    exit;
}
if (! is_string($input['password'] ?? null) || strlen($input['password']) < 24) {
    throw new LogicException('Credential QA random wajib runtime stdin.');
}
Artisan::call('migrate', ['--force' => true]);
app(PaymentsAccessSeeder::class)->run();
$fixture = PaymentHistoryFixture::seed();
foreach (['viewer', 'manager', 'outsider'] as $role) {
    $user = User::create(['name' => 'QA '.$role, 'email' => 'qa-'.$role.'@example.test', 'password' => $input['password']]);
    if ($role === 'viewer') {
        $user->givePermissionTo('finance.payments.history.view');
    }
    if ($role === 'manager') {
        $user->givePermissionTo(['finance.payments.view', 'finance.payments.manage']);
    }
}
echo json_encode(['fingerprint' => PaymentHistoryFixture::fingerprint(), 'fixture' => $fixture]);
