<?php

use App\Models\User;
use App\Modules\Inventory\Items\Application\Actions\SaveItemCatalog;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Application\DTOs\ItemImageUpload;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use App\Modules\Sales\Parties\Application\DTOs\ItemPriceData;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\CustomerLevel;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$stage = 'connection';
set_exception_handler(static function (Throwable $error) use (&$stage): never {
    fwrite(STDERR, 'FAIL item image MySQL: '.$stage.' '.class_basename($error).PHP_EOL);
    exit(1);
});
if (DB::connection()->getDriverName() !== 'mysql') {
    throw new LogicException('QA memerlukan MySQL.');
}
$original = DB::connection()->getConfig();
unset($original['name']);
$server = DB::connection()->getPdo();
$name = 'pos_item_image_qa_'.bin2hex(random_bytes(8));
$root = realpath(__DIR__.'/../../storage/framework/testing');
if ($root === false || ! preg_match('/^pos_item_image_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
    throw new LogicException('Target QA tidak aman.');
}
$directory = $root.DIRECTORY_SEPARATOR.$name;
$created = false;
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    config(['database.connections.item_image_qa' => array_replace($original, ['database' => $name, 'url' => null]),
        'cache.default' => 'array', 'filesystems.disks.local.root' => $directory]);
    DB::setDefaultConnection('item_image_qa');
    Schema::clearResolvedInstance('db.schema');
    $app->detectEnvironment(fn () => 'testing');
    if (DB::selectOne('SELECT DATABASE() AS db')->db !== $name) {
        throw new LogicException('Database QA tidak cocok.');
    }
    $stage = 'migration';
    if (Artisan::call('migrate', ['--force' => true, '--database' => 'item_image_qa']) !== 0) {
        throw new RuntimeException('Migration gagal.');
    }
    auth()->setUser(User::factory()->create());
    $unit = Unit::create(['code' => 'UNIT', 'name' => 'Unit', 'symbol' => 'unit']);
    $level = CustomerLevel::create(['code' => 'ECERAN', 'name' => 'Eceran']);
    $data = new CreateItemData('QA-CAMERA', 'Kamera QA', $unit->id);
    $prices = [new ItemPriceData($unit->id, $level->id, '450000')];
    $upload = UploadedFile::fake()->image('camera.png', 64, 64);
    $image = new ItemImageUpload($upload->getPathname(), 'png');
    $action = app(SaveItemCatalog::class);
    $stage = 'upload';
    $id = $action->save($data, $prices, image: $image);
    $first = Item::findOrFail($id)->media()->sole();
    if (! Str::isUlid($first->id) || $first->model_id !== $id || ! is_file($first->getPath())) {
        throw new RuntimeException('ULID morph atau file gagal.');
    }
    $stage = 'replace';
    $action->save($data, $prices, $id, $image);
    $second = Item::findOrFail($id)->media()->sole();
    if ($second->id === $first->id || is_file($first->getPath()) || ! is_file($second->getPath())) {
        throw new RuntimeException('Replace gagal.');
    }
    $stage = 'remove';
    $action->save($data, $prices, $id, removeImage: true);
    if (Item::findOrFail($id)->media()->exists() || is_file($second->getPath())) {
        throw new RuntimeException('Remove gagal.');
    }
    echo 'PASS: migration MySQL, media/morph ULID, upload/replace/remove dan cleanup file.'.PHP_EOL;
} finally {
    DB::purge('item_image_qa');
    if ($created && preg_match('/^pos_item_image_qa_[a-f0-9]{16}$/D', $name) && $name !== $original['database']) {
        $server->exec('DROP DATABASE `'.$name.'`');
    }
    if (is_dir($directory) && realpath(dirname($directory)) === $root && basename($directory) === $name) {
        File::deleteDirectory($directory);
    }
}
