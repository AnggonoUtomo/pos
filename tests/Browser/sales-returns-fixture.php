<?php

use App\Models\User;
use App\Modules\Sales\Returns\Database\Seeders\ReturnsAccessSeeder;
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
    if (! is_string($input['verify']) || ! hash_equals($input['verify'], SalesReturnFixture::fingerprint())) {
        throw new RuntimeException('Operasi draft mengubah ledger asal.');
    }
    echo json_encode(['ledger' => 'unchanged', 'drafts' => DB::table('sales_returns')->count(),
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
    }
}
echo json_encode(['fingerprint' => SalesReturnFixture::fingerprint(), 'number' => $invoice['number'],
    'invoice_id' => $invoice['id'], 'sku' => $invoice['lines'][0]['sku']], JSON_THROW_ON_ERROR);
