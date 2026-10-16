<?php

use App\Modules\Sales\POS\Database\Seeders\POSAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Aktivasi hanya MySQL local, additive tanpa penjualan demo.');
}
try {
    $permissionIds = DB::table('permissions')->pluck('id')->all();
    $snapshot = static function (string $table, array $columns) use ($permissionIds): string {
        $query = DB::table($table)->select($columns);
        if ($table === 'permissions') {
            $query->whereIn('id', $permissionIds);
        }
        $hashes = $query->get()->map(fn ($row) => hash('sha256', json_encode($row, JSON_THROW_ON_ERROR)))->all();
        sort($hashes);

        return hash('sha256', json_encode($hashes, JSON_THROW_ON_ERROR));
    };
    $before = $columns = [];
    foreach (Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false) as $table) {
        // Permission cache invalidation is derived state, not a role/grant mutation.
        if (! in_array($table, ['migrations', 'cache', 'cache_locks'], true)) {
            $columns[$table] = Schema::getColumnListing($table);
            $before[$table] = $snapshot($table, $columns[$table]);
        }
    }
    if (Artisan::call('migrate', ['--path' => 'app/Modules/Sales/POS/Database/Migrations', '--force' => true]) !== 0) {
        throw new RuntimeException('Migration POS gagal.');
    }
    echo Artisan::output();
    app(POSAccessSeeder::class)->run();
    foreach ($before as $table => $hash) {
        if ($hash !== $snapshot($table, $columns[$table])) {
            throw new RuntimeException('Fingerprint existing berubah; hentikan aktivasi, jangan reset data.');
        }
    }
    foreach (DB::table('permissions')->whereNotIn('id', $permissionIds)->get(['name', 'guard_name']) as $permission) {
        if ($permission->guard_name !== 'web' || ! in_array($permission->name, ['sales.pos.invoices.create', 'sales.pos.invoices.post', 'sales.pos.invoices.view'], true)) {
            throw new RuntimeException('Permission baru di luar scope.');
        }
    }
    foreach (['sales_invoices', 'sales_invoice_lines'] as $table) {
        if (! isset($before[$table]) && DB::table($table)->exists()) {
            throw new RuntimeException('Tabel invoice baru seharusnya kosong.');
        }
    }
    echo "PASS: schema/permission catalog additive; fingerprint bisnis existing identik, tanpa grant/reset/penjualan pengguna.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: '.get_class($exception).' pada '.basename($exception->getFile()).':'.$exception->getLine()."\n");
    exit(1);
}
