<?php

use App\Modules\Finance\Payments\Database\Seeders\PaymentsAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Aktivasi hanya local/MySQL.');
}
foreach (['payment_receipts', 'payment_records', 'payment_disbursements', 'payment_disbursement_lines'] as $table) {
    if (! Schema::hasTable($table)) {
        throw new LogicException('Baseline migration ledger belum lengkap.');
    }
}
$hash = static fn ($value) => hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
$before = [];
foreach (Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false) as $table) {
    if (in_array($table, ['cache', 'cache_locks', 'permissions'], true)) {
        continue;
    }
    $before[$table] = $hash(DB::table($table)->get()->map($hash)->sort()->values()->all());
}
$permissions = DB::table('permissions')->get()->mapWithKeys(fn ($row) => [$row->id => $hash($row)])->all();
DB::transaction(function () use ($before, $permissions, $hash): void {
    app(PaymentsAccessSeeder::class)->run();
    foreach ($before as $table => $value) {
        if ($value !== $hash(DB::table($table)->get()->map($hash)->sort()->values()->all())) {
            throw new RuntimeException('Data existing berubah; rollback catalog.');
        }
    }
    foreach ($permissions as $id => $value) {
        if ($value !== $hash(DB::table('permissions')->where('id', $id)->first())) {
            throw new RuntimeException('Permission existing berubah; rollback catalog.');
        }
    }
});
echo "PASS: permission catalog saja, tanpa grant/seed/transaksi atau migration baru.\n";
echo "PASS: data/account/ledger/audit/migration existing identik.\n";
