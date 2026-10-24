<?php

use App\Models\User;
use App\Modules\Inventory\Transfers\Database\Seeders\TransfersAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Fixtures\TransferDraftFixture;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL fixture transfer: '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(dirname($database)) !== realpath(storage_path('framework/testing'))
    || ! preg_match('/^transfer-draft-browser-[a-f0-9]{16}\.sqlite$/D', basename($database))) {
    throw new LogicException('Fixture hanya SQLite disposable.');
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (($input['verify'] ?? false) === true) {
    if (($input['operational'] ?? false) === true) {
        $draft = DB::table('stock_transfers')->sole();
        if ($draft->status !== 'posted' || DB::table('stock_adjustments')->count() !== 1
            || DB::table('stock_receipts')->where('source_type', 'transfer')->count() !== 1
            || DB::table('stock_issues')->where('source_type', 'transfer')->count() !== 1) {
            throw new RuntimeException('Ledger operasional browser tidak sesuai.');
        }
        echo json_encode(['status' => 'PASS', 'transfer' => 'posted', 'adjustments' => 1]);
        exit;
    }
    $draft = DB::table('stock_transfers')->sole();
    $events = DB::table('activity_log')->where('log_name', 'inventory.transfers')->orderBy('id')->pluck('event')->all();
    if (! hash_equals($input['fingerprint'], TransferDraftFixture::fingerprint()) || $draft->status !== 'draft'
        || $draft->version !== 4 || $draft->deleted_at !== null || $events !== ['created', 'updated', 'deleted', 'restored']) {
        throw new RuntimeException('Lifecycle/snapshot/ledger browser tidak sesuai.');
    }
    echo json_encode(['status' => 'PASS', 'events' => $events, 'stock_finance_master' => 'unchanged']);
    exit;
}
if (! is_string($input['password'] ?? null) || strlen($input['password']) < 24) {
    throw new LogicException('Credential random melalui stdin wajib.');
}
Artisan::call('migrate', ['--force' => true]);
$payload = TransferDraftFixture::seed();
app(TransfersAccessSeeder::class)->run();
foreach (['manager', 'viewer'] as $role) {
    $user = User::create(['name' => 'QA '.$role, 'email' => "qa-{$role}@example.test", 'password' => $input['password'], 'email_verified_at' => now()]);
    $user->givePermissionTo('inventory.transfers.view');
    if ($role === 'manager') {
        $user->givePermissionTo('inventory.transfers.manage');
        if (($input['operational'] ?? false) === true) {
            app(\App\Modules\Inventory\Stock\Database\Seeders\StockAccessSeeder::class)->run();
            $user->givePermissionTo(['inventory.transfers.post', 'inventory.stock.view', 'inventory.stock.adjust']);
        }
    }
}
echo json_encode(['status' => 'READY', 'fingerprint' => TransferDraftFixture::fingerprint(), 'payload' => $payload]);
