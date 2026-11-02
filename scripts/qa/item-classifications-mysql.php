<?php

use App\Modules\Inventory\Items\Application\Actions\CreateItem;
use App\Modules\Inventory\Items\Application\Actions\ManageItemClassification;
use App\Modules\Inventory\Items\Application\Contracts\ItemClassificationRepository;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Application\Enums\ClassificationKind;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL klasifikasi MySQL: '.class_basename($error).' pada '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (PHP_SAPI !== 'cli' || DB::connection()->getDriverName() !== 'mysql') {
    throw new LogicException('QA hanya CLI MySQL.');
}
$original = DB::connection()->getConfig();
unset($original['name']);
$server = DB::connection()->getPdo();
$name = 'pos_classification_qa_'.bin2hex(random_bytes(8));
if (! preg_match('/^pos_classification_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
    throw new LogicException('Target QA tidak aman.');
}
$created = false;
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException($label);
    }
    echo 'PASS: '.$label.PHP_EOL;
};
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connection = array_replace($original, ['database' => $name, 'url' => null]);
    config(['database.connections.classification_qa' => $connection, 'database.connections.classification_peer' => $connection, 'cache.default' => 'array']);
    DB::setDefaultConnection('classification_qa');
    Schema::clearResolvedInstance('db.schema');
    $app->detectEnvironment(fn () => 'testing');
    if (DB::selectOne('SELECT DATABASE() AS db')->db !== $name) {
        throw new LogicException('Koneksi efektif tidak cocok.');
    }
    if (Artisan::call('migrate', ['--force' => true, '--database' => 'classification_qa']) !== 0) {
        throw new RuntimeException('Migration gagal.');
    }
    $kind = ClassificationKind::Category;
    $action = app(ManageItemClassification::class);
    $repository = app(ItemClassificationRepository::class);
    $category = $action->save($kind, ['code' => 'QA', 'name' => 'QA']);
    $unit = Unit::create(['code' => 'UNIT', 'name' => 'Unit', 'symbol' => 'unit']);
    $item = app(CreateItem::class)->handle(new CreateItemData('QA', 'QA', $unit->id));
    $peer = DB::connection('classification_peer');
    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    DB::beginTransaction();
    try {
        DB::table('items')->where('id', $item)->first();
        $peer->table('items')->where('id', $item)->update(['category_id' => $category, 'deleted_at' => now()]);
        $repository->lock($kind, $category);
        $check($repository->referenced($kind, $category), 'Current read melihat referensi soft-deleted yang dibuat setelah snapshot');
    } finally {
        DB::rollBack();
    }
    $rejected = false;
    try {
        $action->delete($kind, $category);
    } catch (ValidationException) {
        $rejected = true;
    }
    $check($rejected, 'Master terpakai tetap tidak dapat dihapus pada MySQL');
    $peer->table('items')->where('id', $item)->update(['category_id' => null]);
    DB::beginTransaction();
    try {
        DB::table('item_categories')->where('id', $category)->first();
        $peer->table('item_categories')->where('id', $category)->update(['is_active' => false]);
        $check(! $repository->active($kind, $category), 'Current read menolak master yang dinonaktifkan setelah snapshot');
    } finally {
        DB::rollBack();
    }
    $peer->statement('SET SESSION innodb_lock_wait_timeout = 1');
    DB::beginTransaction();
    try {
        $repository->lock($kind, $category);
        $blocked = false;
        try {
            $peer->table('item_categories')->where('id', $category)->update(['is_active' => true]);
        } catch (QueryException $error) {
            $blocked = (int) ($error->errorInfo[1] ?? 0) === 1205;
        }
        $check($blocked, 'Lock master menahan perubahan peer sampai transaction selesai');
    } finally {
        DB::rollBack();
    }
    $peer->table('item_categories')->where('id', $category)->update(['is_active' => true]);
    $action->delete($kind, $category);
    $action->restore($kind, $category);
    $check(DB::table('item_categories')->where('id', $category)->whereNull('deleted_at')->exists(), 'Master unused dapat dihapus dan dipulihkan');
} finally {
    DB::purge('classification_peer');
    DB::purge('classification_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo 'PASS: database QA dibersihkan; database toko tidak dimutasi.'.PHP_EOL;
    }
}
