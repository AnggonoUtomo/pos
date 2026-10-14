<?php

use App\Modules\Finance\Payments\Application\Actions\ManagePaymentAccount;
use App\Modules\Finance\Payments\Application\Actions\ManagePaymentMethod;
use App\Modules\Finance\Payments\Application\DTOs\PaymentAccountData;
use App\Modules\Finance\Payments\Application\DTOs\PaymentMethodData;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA hanya MySQL local dengan database disposable baru.');
}
$original = config('database.connections.mysql');
$name = 'pos_payments_qa_'.bin2hex(random_bytes(8));
if (! preg_match('/^pos_payments_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
    throw new LogicException('Nama database QA tidak aman.');
}
$server = DB::connection('mysql')->getPdo();
$created = false;
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException($label);
    } echo "PASS: {$label}\n";
};
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connection = array_replace($original, ['database' => $name]);
    config(['database.connections.payments_qa' => $connection, 'database.connections.payments_peer' => $connection, 'database.default' => 'payments_qa']);
    $app->detectEnvironment(fn () => 'testing');
    Artisan::call('migrate', ['--force' => true]);
    app(PaymentsDemoSeeder::class)->run();
    $snapshot = static fn () => array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson(), ['payment_accounts', 'payment_methods', 'activity_log']);
    $before = $snapshot();
    app(PaymentsDemoSeeder::class)->run();
    $check($before === $snapshot() && DB::table('payment_methods')->count() === 3, 'Demo relasional MySQL idempotent/audited');
    $accounts = app(ManagePaymentAccount::class);
    $methods = app(ManagePaymentMethod::class);
    $account = $accounts->save(new PaymentAccountData('QA-ACCOUNT', 'Kas QA', 'cash'));
    $peer = DB::connection('payments_peer');
    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $peer->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    DB::beginTransaction();
    try {
        DB::table('payment_methods')->where('payment_account_id', $account)->get();
        $peer->table('payment_methods')->insert(['id' => strtolower((string) Str::ulid()), 'code' => 'QA-PEER', 'name' => 'Peer assignment', 'type' => 'cash', 'payment_account_id' => $account, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $rejected = false;
        try {
            $accounts->delete($account);
        } catch (ValidationException) {
            $rejected = true;
        }
        $check($rejected, 'Current reference read melihat metode peer sesudah snapshot lama; delete ditolak');
    } finally {
        DB::rollBack();
    }
    $unused = $accounts->save(new PaymentAccountData('QA-STATE', 'Akun berubah', 'cash'));
    DB::beginTransaction();
    try {
        DB::table('payment_accounts')->where('id', $unused)->first();
        $peer->table('payment_accounts')->where('id', $unused)->update(['is_active' => false]);
        $rejected = false;
        try {
            $methods->save(new PaymentMethodData('QA-STALE', 'Stale', 'cash', $unused));
        } catch (ValidationException) {
            $rejected = true;
        }
        $check($rejected && ! DB::table('payment_methods')->where('code', 'QA-STALE')->exists(), 'Assignment menolak account nonaktif terbaru, bukan snapshot');
    } finally {
        DB::rollBack();
    }
    DB::beginTransaction();
    try {
        DB::table('payment_accounts')->where('id', $unused)->first();
        $peer->table('payment_accounts')->where('id', $unused)->update(['is_active' => true]);
        $accounts->save(new PaymentAccountData('QA-STATE', 'Akun berubah', 'cash', isActive: false), $unused);
        $check(! DB::table('payment_accounts')->where('id', $unused)->lockForUpdate()->value('is_active'), 'Dirty check mutation memakai state current');
    } finally {
        DB::rollBack();
    }
    $peer->table('payment_accounts')->where('id', $unused)->update(['is_active' => true]);
    $method = $methods->save(new PaymentMethodData('QA-RESTORE', 'Restore', 'cash', $unused));
    $methods->delete($method);
    DB::beginTransaction();
    try {
        DB::table('payment_methods')->where('id', $method)->first();
        $peer->table('payment_methods')->where('id', $method)->update(['payment_account_id' => $account]);
        $rejected = false;
        try {
            $methods->restore($method);
        } catch (ValidationException) {
            $rejected = true;
        }
        $check($rejected, 'Restore menolak account assignment yang berubah sesudah consistent read');
    } finally {
        DB::rollBack();
    }
    $rejected = false;
    try {
        DB::transaction(fn () => DB::table('payment_accounts')->where('id', $account)->delete());
    } catch (QueryException $exception) {
        if (($exception->errorInfo[1] ?? null) !== 1451) {
            throw $exception;
        }
        $rejected = true;
    }
    $check($rejected, 'FK RESTRICT mencegah hard delete account terpakai');
} finally {
    DB::purge('payments_peer');
    DB::purge('payments_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: database disposable dibersihkan; database pengguna tidak dimutasi.\n";
    }
}
