<?php

use App\Models\User;
use App\Modules\Sales\Returns\Database\Seeders\ReturnsAccessSeeder;
use Brick\Math\BigDecimal;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Fixtures\SalesReturnFixture;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(dirname($database)) !== realpath(__DIR__.'/../../storage/framework/testing')
    || ! preg_match('/^sales-returns-browser-[a-f0-9]{16}\.sqlite$/D', basename($database))) {
    throw new LogicException('QA retur hanya SQLite disposable.');
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
if (isset($input['verify'])) {
    $posting = ($input['posting'] ?? false) === true;
    if (! is_string($input['verify']) || ! hash_equals($input['verify'], $posting ? SalesReturnFixture::sourceFingerprint() : SalesReturnFixture::fingerprint())) {
        throw new RuntimeException('Operasi draft mengubah ledger asal.');
    }
    if ($posting) {
        $posted = DB::table('sales_returns')->where('status', 'posted')->sole();
        $line = DB::table('stock_receipt_lines')->where('receipt_id', $posted->stock_receipt_id)->sole();
        $balance = DB::table('stock_balances')->where('item_id', $line->item_id)->where('warehouse_id', json_decode($posted->snapshot, true)['warehouse_id'])->sole();
        if (! BigDecimal::of($line->quantity)->isEqualTo($input['expected_quantity'])
            || ! BigDecimal::of($balance->quantity)->isEqualTo(BigDecimal::of($input['initial_balance'])->plus($line->quantity))
            || DB::table('stock_return_allocations')->count() !== 1) {
            throw new RuntimeException('Efek posting retur tidak sesuai.');
        }
    }
    echo json_encode(['ledger' => $posting ? 'source unchanged; stock return verified' : 'unchanged', 'drafts' => DB::table('sales_returns')->count(),
        'events' => DB::table('activity_log')->where('log_name', 'sales.returns')->orderBy('id')->pluck('event')->all()]);
    exit;
}
if (! is_string($input['password'] ?? null) || strlen($input['password']) < 24) {
    throw new LogicException('Credential random QA wajib stdin.');
}
Artisan::call('migrate', ['--force' => true]);
app(ReturnsAccessSeeder::class)->run();
$invoice = SalesReturnFixture::seed();
foreach (['manager', 'viewer', 'outsider'] as $role) {
    $user = User::create(['name' => 'QA '.$role, 'email' => 'qa-'.$role.'@example.test', 'password' => $input['password'], 'email_verified_at' => now()]);
    if ($role !== 'outsider') {
        $user->givePermissionTo('sales.returns.view');
    }
    if ($role === 'manager') {
        $user->givePermissionTo('sales.returns.manage');
        if (($input['posting'] ?? false) === true) {
            $user->givePermissionTo('sales.returns.post');
        }
    }
}
echo json_encode(['fingerprint' => ($input['posting'] ?? false) === true ? SalesReturnFixture::sourceFingerprint() : SalesReturnFixture::fingerprint(),
    'initial_balance' => DB::table('stock_balances')->where('warehouse_id', $invoice['warehouse_id'])->where('item_id', $invoice['lines'][0]['item_id'])->value('quantity'), 'number' => $invoice['number'],
    'invoice_id' => $invoice['id'], 'sku' => $invoice['lines'][0]['sku']], JSON_THROW_ON_ERROR);
