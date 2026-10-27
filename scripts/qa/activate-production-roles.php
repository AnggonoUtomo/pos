<?php

use App\Modules\Platform\Identity\Database\Seeders\ProductionRolesSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL aktivasi role: '.class_basename($error).'. Tidak menampilkan detail credential; periksa prasyarat/katalog/konflik role.'.PHP_EOL);
    exit(1);
});
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (PHP_SAPI !== 'cli' || ! app()->environment(['local', 'production']) || DB::connection()->getDriverName() !== 'mysql'
    || DB::selectOne('SELECT DATABASE() AS db')->db !== DB::connection()->getDatabaseName()) {
    throw new LogicException('Target harus database MySQL aplikasi local/production.');
}
if (! in_array('--apply', $argv, true)) {
    foreach (ProductionRolesSeeder::ROLE_PERMISSIONS as $name => $permissions) {
        $exists = Role::query()->where('name', $name)->where('guard_name', 'web')->exists();
        echo $name.' | '.($exists ? 'existing; periksa matriks saat apply' : 'akan dibuat').' | '.count($permissions).' izin baseline'.PHP_EOL;
    }
    echo 'PREVIEW: tidak ada perubahan. Gunakan --apply setelah backup.'.PHP_EOL;
    exit;
}
$backups = glob(base_path('backups/*.sql')) ?: [];
if (! array_filter($backups, fn ($file) => filemtime($file) > time() - 3600 && filesize($file) > 0)) {
    throw new LogicException('Backup baru wajib tersedia sebelum aktivasi.');
}
$lock = 'pos_production_roles_activation';
if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock])->acquired !== 1) {
    throw new LogicException('Aktivasi lain sedang berjalan.');
}
$fingerprint = static function (string $table, ?array $filter = null): string {
    $columns = DB::connection()->getSchemaBuilder()->getColumnListing($table);
    $query = DB::table($table);
    if ($filter !== null) {
        $query->whereIn($filter[0], $filter[1]);
    }
    foreach ($columns as $column) {
        $query->orderBy($column);
    }
    $hash = hash_init('sha256');
    hash_update($hash, json_encode($columns, JSON_THROW_ON_ERROR));
    foreach ($query->cursor() as $row) {
        hash_update($hash, json_encode((array) $row, JSON_THROW_ON_ERROR));
    }

    return hash_final($hash);
};
try {
    $report = DB::transaction(function () use ($fingerprint): array {
        $tables = array_map(fn ($entry) => array_values((array) $entry)[0], DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"));
        $roleIds = DB::table('roles')->pluck('id')->all();
        $filters = ['roles' => ['id', $roleIds], 'role_has_permissions' => ['role_id', $roleIds],
            'activity_log' => ['id', DB::table('activity_log')->pluck('id')->all()]];
        $before = [];
        foreach ($tables as $table) {
            if (! in_array($table, ['cache', 'cache_locks'], true)) {
                $before[$table] = $fingerprint($table, $filters[$table] ?? null);
            }
        }
        app(ProductionRolesSeeder::class)->run();
        $after = [];
        foreach ($before as $table => $hash) {
            if (! hash_equals($hash, $fingerprint($table, $filters[$table] ?? null))) {
                throw new RuntimeException('Data existing berubah: '.$table);
            }
            $after[$table] = $fingerprint($table);
        }
        app(ProductionRolesSeeder::class)->run();
        foreach ($after as $table => $hash) {
            if (! hash_equals($hash, $fingerprint($table))) {
                throw new RuntimeException('Rerun tidak idempotent: '.$table);
            }
        }
        $roles = Role::query()->where('guard_name', 'web')->whereIn('name', array_keys(ProductionRolesSeeder::ROLE_PERMISSIONS))
            ->with('permissions')->orderBy('name')->get();
        if ($roles->count() !== 7) {
            throw new RuntimeException('Jumlah role produksi tidak sesuai.');
        }

        return ['status' => 'PASS', 'environment' => app()->environment(), 'driver' => 'mysql',
            'preservedTables' => count($before), 'rerun' => 'no-op', 'roles' => $roles->map(fn ($role) => [
                'name' => $role->name, 'guard' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
            ])->all()];
    });
    $directory = storage_path('framework/testing/production-roles');
    if (! is_dir($directory)) {
        mkdir($directory, 0700, true);
    }
    file_put_contents($directory.'/activation.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    echo 'PASS: tujuh role produksi tersedia; '.$report['preservedTables'].' tabel existing terjaga, rerun no-op. Assignment pengguna tidak diubah.'.PHP_EOL;
} finally {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock]);
}
