<?php

use App\Modules\Purchasing\Suppliers\Application\Actions\ManageSupplier;
use App\Modules\Purchasing\Suppliers\Application\DTOs\SupplierData;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA Supplier hanya MySQL local dengan database disposable baru.');
}
$original = config('database.connections.mysql');
$name = 'pos_suppliers_qa_'.bin2hex(random_bytes(8));
if (! preg_match('/^pos_suppliers_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
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
    $connection = array_replace($original, ['database' => $name]);
    config(['database.connections.suppliers_qa' => $connection, 'database.connections.suppliers_peer' => $connection, 'database.default' => 'suppliers_qa']);
    $app->detectEnvironment(fn () => 'testing');
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Migration lengkap native MySQL');
    app(SuppliersDemoSeeder::class)->run();
    $snapshot = static fn () => [DB::table('suppliers')->orderBy('id')->get()->toJson(), DB::table('activity_log')->orderBy('id')->get()->toJson()];
    $before = $snapshot();
    app(SuppliersDemoSeeder::class)->run();
    $check($before === $snapshot() && DB::table('suppliers')->count() === 3, 'Demo MySQL idempotent/audited');
    $action = app(ManageSupplier::class);
    $id = $action->save(new SupplierData('QA-CCTV', 'CCTV QA'));
    $peer = DB::connection('suppliers_peer');
    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    DB::beginTransaction();
    try {
        DB::table('suppliers')->where('id', $id)->first();
        $peer->table('suppliers')->where('id', $id)->update(['is_active' => false, 'phone' => 'peer-private']);
        $logs = Activity::count();
        $action->save(new SupplierData('QA-CCTV', 'CCTV QA'), $id);
        $row = DB::table('suppliers')->where('id', $id)->lockForUpdate()->first();
        $log = Activity::query()->latest('id')->first();
        $check((bool) $row->is_active && $row->phone === null && Activity::count() === $logs + 1
            && ($log->properties['old']['is_active'] ?? null) === false,
            'Locked current read memakai state peer terbaru, audit old bukan snapshot');
    } finally {
        DB::rollBack();
    }
    $action->delete($id);
    $duplicate = false;
    try {
        DB::transaction(fn () => DB::table('suppliers')->insert(['id' => strtolower((string) Str::ulid()), 'code' => 'qa-cctv', 'name' => 'Duplicate']));
    } catch (UniqueConstraintViolationException) {
        $duplicate = true;
    }
    $check($duplicate, 'Unique native case-insensitive termasuk soft-deleted');
    $action->restore($id);
    $before = $snapshot();
    Activity::creating(fn () => false);
    $rejected = false;
    try {
        $action->save(new SupplierData('QA-CCTV', 'Must rollback'), $id);
    } catch (RuntimeException) {
        $rejected = true;
    } finally {
        Activity::flushEventListeners();
    }
    $check($rejected && $before === $snapshot(), 'Audit veto rollback mutation native MySQL');
    $migration = require app_path('Modules/Purchasing/Suppliers/Database/Migrations/2026_10_17_000001_create_suppliers_table.php');
    $migration->down();
    $migration->up();
    $check(DB::table('suppliers')->count() === 0, 'Migration module down/up disposable');
} finally {
    DB::purge('suppliers_peer');
    DB::purge('suppliers_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: database QA dibersihkan; database pengguna tidak dimutasi.\n";
    }
}
