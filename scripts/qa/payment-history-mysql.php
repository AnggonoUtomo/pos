<?php

use App\Modules\Finance\Payments\Application\Actions\RecordSalePayment;
use App\Modules\Finance\Payments\Application\Contracts\PurchasePaymentWriter;
use App\Modules\Finance\Payments\Application\DTOs\PurchasePaymentRequest;
use App\Modules\Finance\Payments\Application\Queries\ListPaymentHistory;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Fixtures\PaymentHistoryFixture;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA hanya local/MySQL disposable.');
}
$original = config('database.connections.mysql');
$name = 'pos_payment_history_qa_'.bin2hex(random_bytes(8));
if (! preg_match('/^pos_payment_history_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
    throw new LogicException('Nama DB tidak aman.');
}
$server = DB::connection('mysql')->getPdo();
$created = false;
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException($label);
    } echo "PASS: {$label}\n";
};
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    config(['database.connections.history_qa' => array_replace($original, ['database' => $name]), 'app.timezone' => 'UTC']);
    DB::setDefaultConnection('history_qa');
    $app->detectEnvironment(fn () => 'testing');
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Native schema baseline tersedia');
    $fixture = PaymentHistoryFixture::seed();
    $largeSale = '9999999999999999999999999999.99';
    $largeBuy = '999999999999999999999999.123456';
    app(RecordSalePayment::class)->handle(['source_id' => strtolower((string) Str::ulid()), 'source_number' => 'POS-LARGE-QA', 'total' => $largeSale, 'payments' => [['method_id' => $fixture['methods']['TUNAI'], 'amount' => $largeSale]]]);
    $purchase = DB::transaction(fn () => app(PurchasePaymentWriter::class)->pay(new PurchasePaymentRequest(strtolower((string) Str::ulid()), 'PO-LARGE-QA', $largeBuy, [['method_id' => $fixture['methods']['TRANSFER'], 'amount' => $largeBuy]])));
    DB::table('payment_methods')->update(['name' => 'Renamed method', 'deleted_at' => now(), 'is_active' => false]);
    DB::table('payment_accounts')->update(['name' => 'Renamed account', 'deleted_at' => now(), 'is_active' => false, 'account_number' => 'PRIVATE-QA']);
    $before = PaymentHistoryFixture::fingerprint();
    $query = app(ListPaymentHistory::class);
    $filters = ['search' => '', 'direction' => 'all', 'account_id' => null, 'method_id' => null, 'date_from' => null, 'date_to' => null];
    $check($query->records(1, $filters)['meta']['total'] === 5, 'Native union kedua arah/pagination');
    $check($query->records(1, array_replace($filters, ['date_from' => '2026-10-16', 'date_to' => '2026-10-16']))['meta']['total'] === 2, 'Batas tanggal Jakarta inklusif');
    $check($query->records(1, array_replace($filters, ['search' => '%_']))['meta']['total'] === 1, 'Pencarian wildcard literal');
    $check($query->records(1, array_replace($filters, ['account_id' => $fixture['bank_account'], 'method_id' => $fixture['methods']['TUNAI']]))['meta']['total'] === 0, 'Filter akun/metode harus baris yang sama');
    $check($query->records(1, array_replace($filters, ['search' => 'POS-LARGE-QA']))['data'][0]['total'] === $largeSale, 'Nominal sale28digit exact lewat UNION CHAR');
    $check($query->records(1, array_replace($filters, ['search' => 'PO-LARGE-QA']))['data'][0]['total'] === $largeBuy && $query->detail('outgoing', $purchase->id)['total'] === $largeBuy, 'Nominal purchase24digit/6pecahan exact');
    $detail = $query->detail('incoming', $fixture['incoming']);
    $cash = collect($detail['payments'])->firstWhere('method_type', 'cash');
    $check($detail['change'] === '1000.00' && $cash['amount'] === '4000.12', 'Kas masuk bersih sesudah kembalian');
    $options = $query->options();
    $check(count($options['accounts']) === 2 && count($options['methods']) === 2 && ! str_contains(json_encode([$detail, $options]), 'Renamed') && ! str_contains(json_encode($detail), 'PRIVATE-QA'), 'Snapshot dan pilihan tidak bergantung master aktif, metadata privat tidak bocor');
    $check($before === PaymentHistoryFixture::fingerprint(), 'Seluruh GET query menjaga data/ledger/audit identik');
} finally {
    DB::purge('history_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: DB QA dibersihkan, DB user tidak dimutasi.\n";
    }
}
