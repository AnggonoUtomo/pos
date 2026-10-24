<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\Fixtures\MvpOperationalScenario;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    $code = $error instanceof QueryException ? implode('/', array_slice($error->errorInfo ?? [], 0, 2)) : '';
    $table = '';
    if ($error instanceof QueryException && preg_match('/create table [`"]([^`"]+)[`"]/i', $error->getSql(), $match)) {
        $table = ' create-table='.$match[1].' connection='.$error->getConnectionName();
    }
    fwrite(STDERR, 'FAIL MVP MySQL: '.class_basename($error).' '.$code.$table.' pada '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (! app()->environment('local') || DB::connection()->getDriverName() !== 'mysql') {
    throw new LogicException('QA hanya local/MySQL, bukan database toko.');
}
$original = DB::connection()->getConfig();
unset($original['name']);
$server = DB::connection()->getPdo();
$name = 'pos_mvp_qa_'.bin2hex(random_bytes(8));
$created = false;
try {
    if (! preg_match('/^pos_mvp_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
        throw new LogicException('Nama database tidak aman.');
    }
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    config(['database.connections.mvp_qa' => array_replace($original, ['database' => $name, 'url' => null]), 'cache.default' => 'array']);
    DB::setDefaultConnection('mvp_qa');
    Schema::clearResolvedInstance('db.schema');
    if (DB::selectOne('SELECT DATABASE() AS db')->db !== $name) {
        throw new LogicException('Database aktual tidak sesuai target QA.');
    }
    app(PermissionRegistrar::class)->initializeCache();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $app->detectEnvironment(fn () => 'testing');
    if (Artisan::call('migrate', ['--force' => true, '--database' => 'mvp_qa']) !== 0) {
        throw new RuntimeException('Migration QA gagal.');
    }
    echo json_encode(MvpOperationalScenario::run(), JSON_THROW_ON_ERROR).PHP_EOL;
} finally {
    DB::purge('mvp_qa');
    if ($created && preg_match('/^pos_mvp_qa_[a-f0-9]{16}$/D', $name) && $name !== $original['database']) {
        $server->exec('DROP DATABASE `'.$name.'`');
    }
}
