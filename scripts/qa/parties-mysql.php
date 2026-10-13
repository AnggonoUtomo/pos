<?php

use App\Modules\Sales\Parties\Application\Actions\ManageCustomer;
use App\Modules\Sales\Parties\Application\Actions\ManageCustomerLevel;
use App\Modules\Sales\Parties\Application\DTOs\CustomerData;
use App\Modules\Sales\Parties\Application\DTOs\CustomerLevelData;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA hanya local MySQL dengan database disposable baru.');
}
$original = config('database.connections.mysql');
$name = 'pos_parties_qa_'.bin2hex(random_bytes(8));
if (! preg_match('/^pos_parties_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
    throw new LogicException('Nama database QA tidak aman.');
}
$server = DB::connection('mysql')->getPdo();
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException($label);
    }
    echo 'PASS: '.$label."\n";
};
$created = false;
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connection = array_replace($original, ['database' => $name]);
    config(['database.connections.parties_qa' => $connection, 'database.connections.parties_peer' => $connection, 'database.default' => 'parties_qa']);
    $app->detectEnvironment(fn () => 'testing');
    Artisan::call('migrate', ['--force' => true]);
    app(PartiesDemoSeeder::class)->run();
    $snapshot = static fn () => array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson(), ['customer_levels', 'customers', 'activity_log']);
    $before = $snapshot();
    app(PartiesDemoSeeder::class)->run();
    $check($before === $snapshot(), 'Demo MySQL relasional idempotent dan audited');
    $level = app(ManageCustomerLevel::class)->save(new CustomerLevelData('QA-LEVEL', 'Level QA'));
    $peer = DB::connection('parties_peer');
    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $peer->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $customer = strtolower(Str::ulid());
    DB::beginTransaction();
    try {
        DB::table('customers')->where('customer_level_id', $level)->get();
        $peer->table('customers')->insert(['id' => $customer, 'code' => 'QA-PEER', 'name' => 'Peer assignment', 'customer_level_id' => $level, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now()]);
        $rejected = false;
        try {
            app(ManageCustomerLevel::class)->delete($level);
        } catch (InvalidArgumentException) {
            $rejected = true;
        }
        $check($rejected && DB::table('customer_levels')->where('id', $level)->value('deleted_at') === null,
            'Current locking read melihat assignment peer sesudah consistent read; delete ditolak');
    } finally {
        DB::rollBack();
    }
    $inactive = app(ManageCustomerLevel::class)->save(new CustomerLevelData('QA-INACTIVE', 'Level berubah'));
    DB::beginTransaction();
    try {
        DB::table('customer_levels')->where('id', $inactive)->first();
        $peer->table('customer_levels')->where('id', $inactive)->update(['is_active' => false]);
        $rejected = false;
        try {
            app(ManageCustomer::class)->save(new CustomerData('QA-STALE', 'CCTV', $inactive));
        } catch (ValidationException) {
            $rejected = true;
        }
        $check($rejected && ! DB::table('customers')->where('code', 'QA-STALE')->exists(), 'Level nonaktif terbaru ditolak setelah snapshot stale');
    } finally {
        DB::rollBack();
    }
    DB::beginTransaction();
    try {
        DB::table('customer_levels')->where('id', $inactive)->first();
        $peer->table('customer_levels')->where('id', $inactive)->update(['is_active' => true]);
        app(ManageCustomerLevel::class)->save(new CustomerLevelData('QA-INACTIVE', 'Level berubah', false), $inactive);
        $check(! DB::table('customer_levels')->where('id', $inactive)->lockForUpdate()->value('is_active'),
            'Mutation memakai state terkini, bukan dirty check dari snapshot lama');
    } finally {
        DB::rollBack();
    }
    $id = app(ManageCustomer::class)->save(new CustomerData('QA-CUSTOMER', 'Kelistrikan', $level));
    app(ManageCustomer::class)->delete($id);
    $rejected = false;
    try {
        app(ManageCustomerLevel::class)->save(new CustomerLevelData('QA-LEVEL', 'Level QA', false), $level);
    } catch (InvalidArgumentException) {
        $rejected = true;
    }
    $check($rejected, 'Level terpakai termasuk customer trashed menolak deactivate');
    $rejected = false;
    try {
        app(ManageCustomer::class)->save(new CustomerData('qa-customer', 'Duplikat', $level));
    } catch (UniqueConstraintViolationException) {
        $rejected = true;
    }
    $check($rejected, 'Kode customer trashed tetap direservasi MySQL');
    $rejected = false;
    try {
        DB::transaction(fn () => DB::table('customer_levels')->where('id', $level)->delete());
    } catch (QueryException) {
        $rejected = true;
    }
    $check($rejected, 'Foreign key RESTRICT mencegah hard delete level terpakai');
} finally {
    DB::purge('parties_peer');
    DB::purge('parties_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: database disposable dibersihkan; database user tidak dimutasi.\n";
    }
}
