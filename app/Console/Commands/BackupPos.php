<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Throwable;

class BackupPos extends Command
{
    protected $signature = 'pos:backup {--verify : Restore ke database disposable dan bandingkan fingerprint}';

    protected $description = 'Backup MySQL ke storage private; verifikasi restore tanpa menimpa database toko';

    public function handle(): int
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql') {
            $this->error('Backup ini hanya mendukung MySQL.');

            return self::FAILURE;
        }
        $lock = Cache::lock('pos:database-backup', 900);
        if (! $lock->get()) {
            $this->error('Backup lain sedang berjalan. Coba lagi setelah selesai.');

            return self::FAILURE;
        }
        $name = null;
        $target = null;
        $created = false;
        $file = null;
        $stage = 'initialization';
        try {
            $config = $connection->getConfig();
            unset($config['name']);
            $name = $connection->getDatabaseName();
            $directory = base_path('backups');
            if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
                throw new \RuntimeException('Directory backup tidak tersedia.');
            }
            $file = $directory.'/pos-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sql';
            $stage = 'source-fingerprint';
            $before = $this->option('verify') ? $this->fingerprint($connection->getName()) : null;
            $credentials = ['--host='.($config['host'] ?? '127.0.0.1'), '--port='.($config['port'] ?? 3306), '--user='.($config['username'] ?? '')];
            $process = new Process(['mysqldump', ...$credentials, '--single-transaction', '--quick', '--skip-lock-tables', '--hex-blob',
                '--no-tablespaces', '--set-gtid-purged=OFF', '--default-character-set=utf8mb4', '--result-file='.$file, $name],
                env: ['MYSQL_PWD' => $config['password'] ?? ''], timeout: 300);
            $stage = 'dump';
            $process->run();
            if (! $process->isSuccessful() || ! is_file($file) || filesize($file) === 0) {
                if (is_file($file)) {
                    unlink($file);
                }
                throw new \RuntimeException('mysqldump gagal.');
            }
            if ($this->option('verify')) {
                $target = 'pos_restore_qa_'.bin2hex(random_bytes(8));
                if (! preg_match('/^pos_restore_qa_[a-f0-9]{16}$/D', $target) || $target === $name) {
                    throw new \LogicException('Target restore tidak aman.');
                }
                $stage = 'create-disposable';
                $connection->statement('CREATE DATABASE `'.$target.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                $created = true;
                $input = fopen($file, 'rb');
                try {
                    $restore = new Process(['mysql', ...$credentials, '--default-character-set=utf8mb4', '--binary-mode', $target],
                        env: ['MYSQL_PWD' => $config['password'] ?? ''], input: $input, timeout: 300);
                    $stage = 'restore';
                    $restore->run();
                    if (! $restore->isSuccessful()) {
                        throw new \RuntimeException('Restore disposable gagal.');
                    }
                } finally {
                    if (is_resource($input)) {
                        fclose($input);
                    }
                }
                config(['database.connections.pos_restore_verify' => array_replace($config, ['database' => $target, 'url' => null])]);
                if (DB::connection('pos_restore_verify')->selectOne('SELECT DATABASE() AS db')->db !== $target) {
                    throw new \LogicException('Database restore aktual tidak sesuai.');
                }
                $stage = 'compare-fingerprint';
                if (! hash_equals($before, $this->fingerprint('pos_restore_verify')) || ! hash_equals($before, $this->fingerprint($connection->getName()))) {
                    throw new \RuntimeException('Fingerprint tidak cocok atau data sumber berubah saat verifikasi.');
                }
                $this->info('PASS: restore disposable identik; database toko tidak ditimpa.');
            }
            $this->info('Backup tersimpan: backups/'.basename($file));

            return self::SUCCESS;
        } catch (Throwable $error) {
            $this->error('Tahap: '.$stage.' ('.class_basename($error).').');
            $this->error('Backup/verifikasi gagal. Periksa tool MySQL, akses database, kapasitas disk, dan hentikan transaksi saat verifikasi. Detail credential tidak ditampilkan.');

            return self::FAILURE;
        } finally {
            $lock->release();
            DB::purge('pos_restore_verify');
            if ($created && is_string($target) && preg_match('/^pos_restore_qa_[a-f0-9]{16}$/D', $target) && $target !== $name) {
                $connection->statement('DROP DATABASE `'.$target.'`');
            }
        }
    }

    private function fingerprint(string $connection): string
    {
        $db = DB::connection($connection);
        $hash = hash_init('sha256');
        $tables = $db->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $names = array_map(fn ($row) => array_values((array) $row)[0], $tables);
        sort($names);
        foreach ($names as $table) {
            $columns = $db->getSchemaBuilder()->getColumnListing($table);
            $primary = collect($db->getSchemaBuilder()->getIndexes($table))->firstWhere('primary', true)['columns'] ?? $columns;
            $query = $db->table($table);
            foreach ($primary as $column) {
                $query->orderBy($column);
            }
            hash_update($hash, $table.json_encode($columns, JSON_THROW_ON_ERROR));
            foreach ($query->cursor() as $row) {
                hash_update($hash, json_encode((array) $row, JSON_THROW_ON_ERROR));
            }
        }

        return hash_final($hash);
    }
}
