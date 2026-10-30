<?php

use App\Modules\Platform\AuditLog\Application\Queries\ListAuditLog;
use App\Modules\Platform\AuditLog\Database\Seeders\AuditLogAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL aktivasi AuditLog: '.class_basename($error).' pada '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (DB::connection()->getDriverName() !== 'mysql' || ! in_array(app()->environment(), ['local', 'production'], true)) {
    throw new LogicException('Aktivasi hanya MySQL toko local/production.');
}
if (! Schema::hasColumns('activity_log', ['id', 'subject_id', 'properties', 'attribute_changes'])) {
    throw new LogicException('Schema audit existing belum mendukung projection ini.');
}
$fingerprint = static function (string $table, ?array $ids = null): string {
    $query = DB::table($table);
    if ($ids !== null) {
        $query->whereIn('id', $ids);
    }
    foreach (Schema::getColumnListing($table) as $column) {
        $query->orderBy($column);
    }
    $hash = hash_init('sha256');
    foreach ($query->cursor() as $row) {
        hash_update($hash, json_encode((array) $row, JSON_THROW_ON_ERROR));
    }

    return hash_final($hash);
};
if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', ['pos_audit_log_activation'])->acquired !== 1) {
    throw new LogicException('Aktivasi lain sedang berjalan.');
}
try {
    $permissionIds = DB::table('permissions')->pluck('id')->all();
    $before = [];
    foreach (Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false) as $table) {
        if (in_array($table, ['cache', 'cache_locks', 'sessions'], true)) {
            continue;
        }
        $before[$table] = $fingerprint($table, $table === 'permissions' ? $permissionIds : null);
    }
    app(AuditLogAccessSeeder::class)->run();
    app(AuditLogAccessSeeder::class)->run();
    $query = app(ListAuditLog::class);
    $filters = ['search' => '', 'module' => null, 'event' => null, 'actor_id' => null, 'date_from' => null, 'date_to' => null];
    $records = $query->records(1, $filters);
    $query->records(1, array_replace($filters, ['search' => '%_!']));
    $query->options();
    if ($records['data'] !== []) {
        $query->detail($records['data'][0]['id']);
    }
    if (Artisan::call('route:cache') !== 0) {
        throw new RuntimeException('Route cache gagal dibangun.');
    }
    foreach ($before as $table => $hash) {
        if (! hash_equals($hash, $fingerprint($table, $table === 'permissions' ? $permissionIds : null))) {
            throw new RuntimeException('Data existing berubah; periksa tanpa reset.');
        }
    }
    if (DB::table('permissions')->where('guard_name', 'web')->where('name', 'platform.audit-log.view')->count() !== 1) {
        throw new RuntimeException('Catalog akses tidak sesuai.');
    }
    echo 'PASS: catalog idempotent tanpa grant; query/detail/JSON MySQL dan route cache aktif; seluruh data existing identik.'.PHP_EOL;
} finally {
    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', ['pos_audit_log_activation']);
}
