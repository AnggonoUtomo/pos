<?php

namespace Database\Seeders\Support;

use App\Models\User;
use Database\Seeders\OperationalLifecycleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;

final class ReplaceOperationalTrainingOnce
{
    public static function tableHash(string $table, ?array $ids = null): string
    {
        $columns = Schema::getColumnListing($table);
        $query = DB::table($table);
        if ($ids !== null) {
            $query->whereIn('id', $ids);
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
    }

    public static function fingerprint(): string
    {
        $hashes = [];
        foreach (OperationalLifecycleSeeder::BUSINESS_TABLES as $table) {
            $hashes[$table] = self::tableHash($table);
        }

        return hash('sha256', json_encode($hashes, JSON_THROW_ON_ERROR));
    }

    public function run(string $expectedFingerprint, int $actorId, bool $allowProduction = false): array
    {
        if (! app()->environment(['local', 'testing']) && ! ($allowProduction && PHP_SAPI === 'cli')) {
            throw new LogicException('Penggantian production memerlukan persetujuan CLI khusus.');
        }

        return DB::transaction(function () use ($expectedFingerprint, $actorId, $allowProduction): array {
            $actor = User::query()->whereKey($actorId)->lockForUpdate()->firstOrFail();
            if (OperationalLifecycleSeeder::marker()) {
                throw new LogicException('Izin penggantian satu kali sudah digunakan. Tidak ada reset kedua.');
            }
            if (! hash_equals($expectedFingerprint, self::fingerprint())) {
                throw new LogicException('Data lama berubah sejak persetujuan. Tidak ada penghapusan.');
            }
            $tables = array_column(Schema::getTables(Schema::getCurrentSchemaListing()), 'name');
            $business = OperationalLifecycleSeeder::BUSINESS_TABLES;
            $foreignKeys = [];
            $protected = [];
            $auditIds = DB::table('activity_log')->pluck('id')->all();
            foreach ($tables as $table) {
                $foreignKeys[$table] = Schema::getForeignKeys($table);
                if (! in_array($table, $business, true) && ! in_array($table, ['cache', 'cache_locks'], true)) {
                    $protected[$table] = self::tableHash($table, $table === 'activity_log' ? $auditIds : null);
                }
                foreach ($foreignKeys[$table] as $key) {
                    if (! in_array($table, $business, true) && in_array($key['foreign_table'], $business, true)) {
                        throw new LogicException('Data dilindungi merujuk data bisnis: '.$table);
                    }
                }
            }
            $beforeCounts = [];
            foreach ($business as $table) {
                $beforeCounts[$table] = DB::table($table)->count();
            }
            // Return/transfer provenance closes a FK cycle through FIFO -> movement -> receipt line.
            // Clear only this nullable business link inside the rollbackable, one-time replacement.
            $origin = collect(Schema::getColumns('stock_receipt_lines'))->firstWhere('name', 'origin_fifo_layer_id');
            if (! ($origin['nullable'] ?? false)) {
                throw new LogicException('Link asal FIFO tidak nullable; penggantian dihentikan.');
            }
            DB::table('stock_receipt_lines')->whereNotNull('origin_fifo_layer_id')->update(['origin_fifo_layer_id' => null]);
            $foreignKeys['stock_receipt_lines'] = array_values(array_filter($foreignKeys['stock_receipt_lines'],
                fn ($key) => $key['columns'] !== ['origin_fifo_layer_id']));
            $pending = $business;
            while ($pending !== []) {
                $deleted = false;
                foreach ($pending as $index => $table) {
                    $blocked = false;
                    foreach ($pending as $child) {
                        if ($child !== $table && in_array($table, array_column($foreignKeys[$child], 'foreign_table'), true)) {
                            $blocked = true;
                            break;
                        }
                    }
                    if ($blocked) {
                        continue;
                    }
                    $selfKeys = array_values(array_filter($foreignKeys[$table], fn ($key) => $key['foreign_table'] === $table));
                    if ($selfKeys !== []) {
                        if (count($selfKeys) !== 1 || count($selfKeys[0]['columns']) !== 1 || $selfKeys[0]['foreign_columns'] !== ['id']) {
                            throw new LogicException('Self reference tidak didukung untuk reset aman: '.$table);
                        }
                        while (DB::table($table)->exists()) {
                            $references = DB::table($table)->whereNotNull($selfKeys[0]['columns'][0])->pluck($selfKeys[0]['columns'][0])->all();
                            if (DB::table($table)->whereNotIn('id', $references)->delete() === 0) {
                                throw new LogicException('Siklus referensi pada '.$table);
                            }
                        }
                    } else {
                        DB::table($table)->delete();
                    }
                    unset($pending[$index]);
                    $deleted = true;
                }
                if (! $deleted) {
                    throw new LogicException('Siklus foreign key. Penggantian dibatalkan tanpa mematikan constraint.');
                }
            }
            $result = app(OperationalLifecycleSeeder::class)->run($actorId, $allowProduction);
            foreach ($protected as $table => $hash) {
                if (! hash_equals($hash, self::tableHash($table, $table === 'activity_log' ? $auditIds : null))) {
                    throw new LogicException('Data dilindungi berubah: '.$table);
                }
            }
            activity('training')->causedBy($actor)->event('training.lifecycle.replaced_once')
                ->withProperties(['dataset' => OperationalLifecycleSeeder::DATASET, 'old_fingerprint' => $expectedFingerprint,
                    'deleted_counts' => $beforeCounts, 'protected_tables' => count($protected)])
                ->log('One-time replacement of operational learning data approved by user');

            return ['result' => $result, 'deleted_counts' => $beforeCounts, 'protected_tables' => count($protected)];
        }, 3);
    }
}
