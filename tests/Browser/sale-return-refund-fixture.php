<?php

use App\Models\User;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsAccessSeeder;
use App\Modules\Sales\Returns\Application\Actions\SettleReturnRefund;
use App\Modules\Sales\Returns\Application\Queries\QuoteReturnRefund;
use App\Modules\Sales\Returns\Database\Seeders\ReturnsAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Fixtures\ReturnRefundFixture;
use Tests\Fixtures\SalesReturnFixture;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(dirname($database)) !== realpath(__DIR__.'/../../storage/framework/testing')
    || ! preg_match('/^return-refund-browser-[a-f0-9]{16}\.sqlite$/D', basename($database))) {
    throw new LogicException('QA refund hanya SQLite disposable.');
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (isset($input['verify'])) {
    if (! hash_equals($input['verify'], SalesReturnFixture::fingerprint())) {
        throw new RuntimeException('Refund mengubah Stock/source invoice/penerimaan.');
    }
    $settlements = DB::table('sales_return_settlements')->count();
    $refunds = DB::table('payment_refunds')->count();
    if ($settlements !== $input['settlements'] || $refunds !== $input['refunds']
        || DB::table('activity_log')->where('log_name', 'sales.returns')->where('event', 'refund_settled')->count() !== $settlements
        || DB::table('activity_log')->where('log_name', 'finance.payments')->where('event', 'refunded')->count() !== $refunds) {
        throw new RuntimeException('Settlement/payout/audit duplikat atau hilang.');
    }
    echo json_encode(['source_and_stock' => 'unchanged', 'settlements' => $settlements, 'refunds' => $refunds, 'audits' => 'exact']);
    exit;
}
if (isset($input['settle'])) {
    $quote = app(QuoteReturnRefund::class)->handle($input['settle']);
    app(SettleReturnRefund::class)->handle($input['settle'], ['version' => $quote['version'], 'quote_fingerprint' => $quote['quote_fingerprint'],
        'payments' => $quote['total'] === '0.00' ? [] : [['method_id' => DB::table('payment_methods')->where('code', 'TRANSFER')->value('id'), 'amount' => $quote['total']]]]);
    echo json_encode(['settled' => true]);
    exit;
}
if (! is_string($input['password'] ?? null) || strlen($input['password']) < 24) {
    throw new LogicException('Credential random wajib stdin.');
}
Artisan::call('migrate', ['--force' => true]);
app(ReturnsAccessSeeder::class)->run();
app(PaymentsAccessSeeder::class)->run();
$invoice = ReturnRefundFixture::invoice();
$first = ReturnRefundFixture::posted($invoice, '0.4');
$target = ReturnRefundFixture::posted($invoice, '0.3');
$sibling = ReturnRefundFixture::posted($invoice, '0.3');
$tiny = ReturnRefundFixture::invoice('0.01');
$zero = ReturnRefundFixture::posted($tiny, '0.000001');
$cent = ReturnRefundFixture::posted($tiny, '0.999999');
foreach (['manager', 'viewer', 'outsider'] as $role) {
    $user = User::create(['name' => 'QA '.$role, 'email' => 'qa-'.$role.'@example.test', 'password' => $input['password'], 'email_verified_at' => now()]);
    if ($role !== 'outsider') {
        $user->givePermissionTo('sales.returns.view');
    }
    if ($role === 'manager') {
        $user->givePermissionTo(['sales.returns.refund', 'finance.payments.history.view']);
    }
}
$numbers = DB::table('sales_returns')->pluck('number', 'id')->all();
echo json_encode(['first' => $first, 'target' => $target, 'sibling' => $sibling, 'zero' => $zero, 'cent' => $cent,
    'numbers' => $numbers, 'methods' => DB::table('payment_methods')->pluck('id', 'code')->all(),
    'fingerprint' => SalesReturnFixture::fingerprint()], JSON_THROW_ON_ERROR);
