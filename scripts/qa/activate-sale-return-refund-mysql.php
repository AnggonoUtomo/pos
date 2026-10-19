<?php

use App\Models\User;
use App\Modules\Sales\Returns\Application\Actions\SettleReturnRefund;
use App\Modules\Sales\Returns\Application\Queries\QuoteReturnRefund;
use App\Modules\Sales\Returns\Application\Queries\ReturnWorkspace;
use App\Modules\Sales\Returns\Database\Seeders\ReturnsAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\ReturnRefundFixture;
use Tests\Fixtures\SalesReturnFixture;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL: QA aktivasi refund gagal pada '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA aktivasi hanya local/MySQL disposable.');
}
$activation = base_path('scripts/qa/activate-sale-return-refund-local.php');
if (! is_file($activation)) {
    throw new LogicException('Script aktivasi refund belum tersedia.');
}
$original = config('database.connections.mysql');
$name = 'pos_refund_activation_qa_'.bin2hex(random_bytes(8));
if (! preg_match('/^pos_refund_activation_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
    throw new LogicException('Nama database QA tidak aman.');
}
$server = DB::connection('mysql')->getPdo();
$created = false;
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException($label);
    }
    echo "PASS: {$label}\n";
};
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    config(['database.connections.activation_qa' => array_replace($original, ['database' => $name, 'url' => null]), 'cache.default' => 'array']);
    DB::setDefaultConnection('activation_qa');
    $check(DB::selectOne('SELECT DATABASE() AS name')->name === $name, 'Koneksi disposable diverifikasi sebelum migration');
    $migrator = app('migrator');
    $targets = ['2026_10_19_000001_create_payment_refunds', '2026_10_19_000002_create_sales_return_settlements'];
    $files = array_diff_key($migrator->getMigrationFiles(array_merge($migrator->paths(), [database_path('migrations')])), array_flip($targets));
    $check(Artisan::call('migrate', ['--path' => array_values($files), '--realpath' => true, '--force' => true]) === 0, 'Baseline sebelum kedua migration refund tersedia');
    $app->detectEnvironment(fn () => 'testing');
    $invoice = SalesReturnFixture::seed();
    $id = ReturnRefundFixture::posted($invoice, '1');
    app(ReturnsAccessSeeder::class)->run();
    Permission::where('name', 'sales.returns.refund')->where('guard_name', 'web')->delete();
    User::firstOrFail()->assignRole(Role::findOrCreate('qa-operator', 'web')->givePermissionTo('sales.returns.view'));
    $before = SalesReturnFixture::fingerprint();
    $grants = hash('sha256', json_encode([DB::table('roles')->get(), DB::table('model_has_roles')->get(), DB::table('role_has_permissions')->get(), DB::table('model_has_permissions')->get()]));
    $check(! app(ReturnWorkspace::class)->records(1, '', false)['data'][0]['refund_available'], 'Readiness sebelum aktivasi false tanpa mutation');
    $run = static function () use ($activation, $name, $check): void {
        $process = proc_open([PHP_BINARY, $activation, '--qa-database', $name], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
        if (! is_resource($process)) {
            throw new RuntimeException('Proses aktivasi tidak tersedia.');
        }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $check(proc_close($process) === 0 && str_contains($output, 'PASS:') && $error === '', 'Child aktivasi aman dan fingerprint PASS');
    };
    $run();
    $run();
    $check(app(ReturnWorkspace::class)->records(1, '', false)['data'][0]['refund_available'], 'Readiness sesudah aktivasi true');
    foreach (['payment_refunds', 'payment_refund_lines', 'sales_return_refund_scopes', 'sales_return_settlements', 'sales_return_refund_allocations'] as $table) {
        $check(DB::table($table)->count() === 0, 'Aktivasi tidak membuat transaksi '.$table);
    }
    $check(Permission::where('name', 'sales.returns.refund')->where('guard_name', 'web')->count() === 1, 'Catalog refund additive tanpa duplikasi');
    $check($before === SalesReturnFixture::fingerprint(), 'Source/Stock/penerimaan existing identik');
    $check($grants === hash('sha256', json_encode([DB::table('roles')->get(), DB::table('model_has_roles')->get(), DB::table('role_has_permissions')->get(), DB::table('model_has_permissions')->get()])), 'Role dan grant existing identik');
    $quote = app(QuoteReturnRefund::class)->handle($id);
    app(SettleReturnRefund::class)->handle($id, ['version' => $quote['version'], 'quote_fingerprint' => $quote['quote_fingerprint'],
        'payments' => [['method_id' => DB::table('payment_methods')->where('code', 'TRANSFER')->value('id'), 'amount' => $quote['total']]]]);
    $run();
    $check(DB::table('payment_refunds')->count() === 1 && DB::table('sales_return_settlements')->count() === 1, 'Rerun sesudah settlement mempertahankan ledger dan alokasi');
    $check($before === SalesReturnFixture::fingerprint(), 'Refund QA tidak mengubah Stock/source');
} finally {
    DB::purge('activation_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: Database QA dibersihkan, DB user tidak dimutasi.\n";
    }
}
