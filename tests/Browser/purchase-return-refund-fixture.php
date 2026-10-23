<?php

use App\Models\User;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsAccessSeeder;
use App\Modules\Purchasing\Returns\Database\Seeders\ReturnsAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Fixtures\PurchaseReturnRefundFixture;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(dirname($database)) !== realpath(storage_path('framework/testing'))
    || ! preg_match('/^purchasing-returns-browser-[a-f0-9]{16}\.sqlite$/D', basename($database))) {
    throw new LogicException('Fixture hanya SQLite disposable.');
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (($input['verify'] ?? false) === true) {
    if (! hash_equals($input['fingerprint'], PurchaseReturnRefundFixture::fingerprint())
        || DB::table('purchase_refund_receipts')->count() !== 1
        || DB::table('purchase_return_settlements')->count() !== 2
        || DB::table('activity_log')->where('event', 'supplier_refund_received')->count() !== 1
        || DB::table('activity_log')->where('log_name', 'purchasing.returns')->where('event', 'refund_settled')->count() !== 2) {
        throw new RuntimeException('Ledger/audit/source browser tidak sesuai.');
    }
    echo json_encode(['status' => 'PASS', 'receipt' => 'single', 'zero' => 'no_ledger', 'stock_source' => 'unchanged']);
    exit;
}
if (! is_string($input['password'] ?? null) || strlen($input['password']) < 24) {
    throw new LogicException('Credential random melalui stdin wajib.');
}
Artisan::call('migrate', ['--force' => true]);
app(ReturnsAccessSeeder::class)->run();
app(PaymentsAccessSeeder::class)->run();
foreach (['manager', 'viewer'] as $role) {
    $user = User::create(['name' => 'QA '.$role, 'email' => "qa-{$role}@example.test", 'password' => $input['password'], 'email_verified_at' => now()]);
    $user->givePermissionTo(['purchasing.returns.view', 'finance.payments.history.view']);
    if ($role === 'manager') {
        $user->givePermissionTo('purchasing.returns.refund');
    }
}
$normal = PurchaseReturnRefundFixture::posted(PurchaseReturnRefundFixture::purchase(), '0.5');
$zero = PurchaseReturnRefundFixture::posted(PurchaseReturnRefundFixture::purchase('1', '0.000001'), '0.000001');
echo json_encode(['status' => 'READY', 'normal_id' => $normal, 'normal_number' => DB::table('purchase_returns')->where('id', $normal)->value('number'),
    'zero_id' => $zero, 'zero_number' => DB::table('purchase_returns')->where('id', $zero)->value('number'),
    'fingerprint' => PurchaseReturnRefundFixture::fingerprint()], JSON_THROW_ON_ERROR);
