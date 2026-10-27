<?php

use App\Models\User;
use Database\Seeders\OperationalLifecycleSeeder;
use Database\Seeders\Support\ReplaceOperationalTrainingOnce;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Fixtures\MvpOperationalScenario;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL lifecycle MySQL: '.class_basename($error).' pada '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (DB::connection()->getDriverName() !== 'mysql') {
    throw new LogicException('QA memerlukan MySQL.');
}
$original = DB::connection()->getConfig();
unset($original['name']);
$server = DB::connection()->getPdo();
$name = 'pos_lifecycle_qa_'.bin2hex(random_bytes(8));
$created = false;
try {
    if (! preg_match('/^pos_lifecycle_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
        throw new LogicException('Target QA tidak aman.');
    }
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    config(['database.connections.lifecycle_qa' => array_replace($original, ['database' => $name, 'url' => null]), 'cache.default' => 'array']);
    DB::setDefaultConnection('lifecycle_qa');
    Schema::clearResolvedInstance('db.schema');
    if (DB::selectOne('SELECT DATABASE() AS db')->db !== $name) {
        throw new LogicException('Target aktual tidak sama dengan QA.');
    }
    app(PermissionRegistrar::class)->initializeCache();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $app->detectEnvironment(fn () => 'testing');
    if (Artisan::call('migrate', ['--force' => true, '--database' => 'lifecycle_qa']) !== 0) {
        throw new RuntimeException('Migration disposable gagal.');
    }
    $actor = User::factory()->create();
    $actor->assignRole(Role::findOrCreate('super-system', 'web'));
    MvpOperationalScenario::run();
    $old = ReplaceOperationalTrainingOnce::fingerprint();
    $result = app(ReplaceOperationalTrainingOnce::class)->run($old, $actor->id);
    $hash = ReplaceOperationalTrainingOnce::fingerprint();
    $auditCount = Activity::count();
    if ($result['result'] !== app(OperationalLifecycleSeeder::class)->run($actor->id)
        || $hash !== ReplaceOperationalTrainingOnce::fingerprint() || $auditCount !== Activity::count()) {
        throw new RuntimeException('Rerun bukan no-op.');
    }
    $denied = false;
    try {
        app(ReplaceOperationalTrainingOnce::class)->run($hash, $actor->id);
    } catch (LogicException) {
        $denied = true;
    }
    if (! $denied || $hash !== ReplaceOperationalTrainingOnce::fingerprint()) {
        throw new RuntimeException('Reset kedua tidak ditolak.');
    }
    echo 'PASS: MySQL disposable; replacement populated ledger/FIFO/payment, protected tables, angka exact, seed no-op, reset kedua ditolak.'.PHP_EOL;
} finally {
    DB::purge('lifecycle_qa');
    if ($created && preg_match('/^pos_lifecycle_qa_[a-f0-9]{16}$/D', $name) && $name !== $original['database']) {
        $server->exec('DROP DATABASE `'.$name.'`');
    }
}
