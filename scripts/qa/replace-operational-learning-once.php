<?php

use App\Models\User;
use Database\Seeders\OperationalLifecycleSeeder;
use Database\Seeders\Support\ReplaceOperationalTrainingOnce;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Pinned to the dataset explicitly approved by the user on 27 October 2026.
// Never update this fingerprint to authorize another reset.
const APPROVED_OLD_FINGERPRINT = '6158fd7712840fb6d48f2254006992c0eadab342dca5de697c035f1a613e4c63';

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL penggantian sekali: '.class_basename($error).' pada '.basename($error->getFile()).':'.$error->getLine().'. Tidak ada detail credential ditampilkan.'.PHP_EOL);
    exit(1);
});
if (PHP_SAPI !== 'cli' || ! app()->environment(['local', 'production']) || DB::connection()->getDriverName() !== 'mysql'
    || DB::selectOne('SELECT DATABASE() AS db')->db !== DB::connection()->getDatabaseName()) {
    throw new LogicException('Hanya database aktif local/production MySQL yang disetujui.');
}
if (! in_array('--apply-once', $argv, true)) {
    echo json_encode(['mode' => 'READ ONLY PREVIEW', 'already_seeded' => OperationalLifecycleSeeder::marker() !== null,
        'fingerprint' => ReplaceOperationalTrainingOnce::fingerprint(),
        'business_counts' => collect(OperationalLifecycleSeeder::BUSINESS_TABLES)->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all()], JSON_PRETTY_PRINT).PHP_EOL;
    exit;
}
if (OperationalLifecycleSeeder::marker()) {
    throw new LogicException('Penggantian sekali sudah selesai; tidak ada reset kedua.');
}
$backups = glob(base_path('backups/*.sql')) ?: [];
if (! array_filter($backups, fn ($file) => filemtime($file) > time() - 3600 && filesize($file) > 0)) {
    throw new LogicException('Backup baru wajib tersedia.');
}
if (! hash_equals(APPROVED_OLD_FINGERPRINT, ReplaceOperationalTrainingOnce::fingerprint())) {
    throw new LogicException('Fingerprint bukan dataset lama yang disetujui.');
}
$lock = 'pos_learning_dataset_replacement_once';
if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock])->acquired !== 1) {
    throw new LogicException('Proses penggantian lain sedang berjalan.');
}
try {
    $actor = User::query()->where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'super-system')->where('guard_name', 'web'))->orderBy('id')->firstOrFail();
    $result = app(ReplaceOperationalTrainingOnce::class)->run(APPROVED_OLD_FINGERPRINT, $actor->id, allowProduction: true);
    $documents = [];
    foreach (['stock_openings', 'purchase_orders', 'sales_invoices', 'sales_returns', 'purchase_returns', 'stock_transfers', 'stock_adjustments'] as $table) {
        $columns = ['id', 'number'];
        if (Schema::hasColumn($table, 'status')) {
            $columns[] = 'status';
        }
        $documents[$table] = DB::table($table)->select($columns)->orderBy('id')->get()->all();
    }
    $directory = storage_path('framework/testing/operational-lifecycle');
    if (! is_dir($directory)) {
        mkdir($directory, 0700, true);
    }
    file_put_contents($directory.'/activation.json', json_encode($result + ['documents' => $documents], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    echo 'PASS: dataset lama diganti sekali. Pengguna/akses/settings/audit/backup dipertahankan; reset kedua tidak diperbolehkan.'.PHP_EOL;
} finally {
    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock]);
}
