<?php

use App\Models\User;
use App\Modules\Inventory\Transfers\Application\Actions\ManageTransferDraft;
use App\Modules\Inventory\Transfers\Database\Seeders\TransfersAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Fixtures\TransferDraftFixture;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL: QA aktivasi transfer gagal pada '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA aktivasi hanya local/MySQL disposable.');
}
$activation = base_path('scripts/qa/activate-transfer-drafts-local.php');
if (! is_file($activation)) {
    throw new LogicException('Script aktivasi transfer belum tersedia.');
}
$original = config('database.connections.mysql');
$name = 'pos_transfer_activation_qa_'.bin2hex(random_bytes(8));
if (! preg_match('/^pos_transfer_activation_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
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
    config(['permission.cache.store' => 'array']);
    app(PermissionRegistrar::class)->initializeCache();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $check(DB::selectOne('SELECT DATABASE() AS name')->name === $name, 'Koneksi disposable diverifikasi sebelum migration');
    $migrator = app('migrator');
    $targets = ['2026_10_23_000003_create_stock_transfer_drafts'];
    $files = array_diff_key($migrator->getMigrationFiles(array_merge($migrator->paths(), [database_path('migrations')])), array_flip($targets));
    $check(Artisan::call('migrate', ['--path' => array_values($files), '--realpath' => true, '--force' => true]) === 0, 'Baseline sebelum migration draft transfer tersedia');
    $app->detectEnvironment(fn () => 'testing');
    $payload = TransferDraftFixture::seed();
    app(TransfersAccessSeeder::class)->run();
    Permission::where('name', 'inventory.transfers.manage')->where('guard_name', 'web')->delete();
    User::factory()->create()->assignRole(Role::findOrCreate('qa-operator', 'web')->givePermissionTo('inventory.transfers.view'));
    $before = TransferDraftFixture::fingerprint();
    $grants = hash('sha256', json_encode([DB::table('roles')->get(), DB::table('model_has_roles')->get(), DB::table('role_has_permissions')->get(), DB::table('model_has_permissions')->get()]));
    $check(! Schema::hasTable('stock_transfers'), 'Readiness sebelum aktivasi false tanpa mutation');
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
    $check(Schema::hasTable('stock_transfers'), 'Readiness sesudah aktivasi true');
    foreach (['stock_transfers', 'stock_transfer_lines'] as $table) {
        $check(DB::table($table)->count() === 0, 'Aktivasi tidak membuat transaksi '.$table);
    }
    $check(Permission::where('name', 'inventory.transfers.manage')->where('guard_name', 'web')->count() === 1, 'Catalog transfer additive tanpa duplikasi');
    $check($before === TransferDraftFixture::fingerprint(), 'Source/Stock/penerimaan existing identik');
    $check($grants === hash('sha256', json_encode([DB::table('roles')->get(), DB::table('model_has_roles')->get(), DB::table('role_has_permissions')->get(), DB::table('model_has_permissions')->get()])), 'Role dan grant existing identik');
    $id = app(ManageTransferDraft::class)->save($payload);
    $run();
    $check(DB::table('stock_transfers')->where('id', $id)->count() === 1 && DB::table('stock_transfer_lines')->count() === 2, 'Rerun mempertahankan draft dan baris existing');
    $check($before === TransferDraftFixture::fingerprint(), 'Draft QA tidak mengubah Stock/source');
} finally {
    DB::purge('activation_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: Database QA dibersihkan, DB user tidak dimutasi.\n";
    }
}
