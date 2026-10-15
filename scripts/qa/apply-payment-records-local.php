<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Aktivasi hanya MySQL local, additive tanpa transaksi demo.');
}
try {
    $snapshot = static function (string $table, array $columns): string {
        $hashes = DB::table($table)->select($columns)->get()->map(fn ($row) => hash('sha256', json_encode($row, JSON_THROW_ON_ERROR)))->all();
        sort($hashes);

        return hash('sha256', json_encode($hashes, JSON_THROW_ON_ERROR));
    };
    $before = $columns = [];
    // Preserve all application data, not just a hand-maintained master list.
    foreach (Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false) as $table) {
        if ($table !== 'migrations') {
            $columns[$table] = Schema::getColumnListing($table);
            $before[$table] = $snapshot($table, $columns[$table]);
        }
    }
    if (Artisan::call('migrate', ['--path' => 'app/Modules/Finance/Payments/Database/Migrations', '--force' => true]) !== 0) {
        fwrite(STDERR, "FAIL: migration Finance gagal; jangan down schema berisi data.\n");
        exit(1);
    }
    echo Artisan::output();
    foreach ($before as $table => $hash) {
        if ($hash !== $snapshot($table, $columns[$table])) {
            fwrite(STDERR, "FAIL: fingerprint existing berubah; hentikan aktivasi, jangan reset data.\n");
            exit(1);
        }
    }
    foreach (['payment_receipts', 'payment_records'] as $table) {
        if (! isset($before[$table]) && DB::table($table)->exists()) {
            fwrite(STDERR, "FAIL: tabel penerimaan baru seharusnya kosong.\n");
            exit(1);
        }
    }
    echo "PASS: migration additive; semua data existing identik, tanpa seeder/grant/reset/pembayaran pengguna.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: '.get_class($exception).' pada '.basename($exception->getFile()).':'.$exception->getLine()."\n");
    exit(1);
}
