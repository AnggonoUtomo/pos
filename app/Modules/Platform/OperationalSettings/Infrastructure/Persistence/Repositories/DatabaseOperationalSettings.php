<?php

namespace App\Modules\Platform\OperationalSettings\Infrastructure\Persistence\Repositories;

use App\Modules\Platform\OperationalSettings\Application\Contracts\OperationalSettingsRepository;
use App\Modules\Platform\OperationalSettings\Application\Contracts\PrintingPreferences;
use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePreferences;
use App\Modules\Platform\OperationalSettings\Domain\Services\BackupWindow;
use App\Modules\Platform\OperationalSettings\Infrastructure\Persistence\Models\OperationalSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

class DatabaseOperationalSettings implements OperationalSettingsRepository, PrintingPreferences, TablePreferences
{
    private const ID = '00000000000000000000000002';

    public function read(): array
    {
        return Arr::only(OperationalSetting::findOrFail(self::ID)->toArray(), ['receipt_paper', 'backup_enabled', 'backup_time', 'version', 'table_per_page']);
    }

    public function defaultPerPage(): int
    {
        return (int) (OperationalSetting::findOrFail(self::ID)->table_per_page ?? 10);
    }

    public function printing(): array
    {
        return ['paper' => $this->read()['receipt_paper']];
    }

    public function update(array $fields, int $version, ?int $actorId): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Update pengaturan memerlukan transaction.');
        }
        $model = OperationalSetting::query()->lockForUpdate()->findOrFail(self::ID);
        if ($model->version !== $version) {
            throw ValidationException::withMessages(['version' => 'Pengaturan sudah berubah. Muat ulang halaman.']);
        }
        $model->fill($fields);
        if (! $model->isDirty()) {
            return;
        }
        $model->version++;
        if (! $model->save()) {
            throw new RuntimeException('Pengaturan gagal disimpan.');
        }
        $logger = activity('platform.operational-settings')->performedOn($model)->event('updated')->withProperties(['version' => $model->version, 'settings' => $fields]);
        $actorId === null ? $logger->causedByAnonymous() : $logger->causedBy($actorId);
        $log = $logger->log('Pengaturan operasional diperbarui');
        if ($log === null || ! $log->exists) {
            throw new RuntimeException('Audit pengaturan gagal disimpan.');
        }
    }

    public function status(): array
    {
        $run = DB::table('operational_backup_runs')->orderByDesc('scheduled_for')->first();
        $manual = Activity::query()->where('log_name', 'platform.operational-settings')
            ->whereIn('event', ['backup.manual.succeeded', 'backup.manual.failed'])->latest('id')->first();

        return ['scheduler_seen_at' => OperationalSetting::findOrFail(self::ID)->scheduler_seen_at,
            'last_run' => $run ? Arr::only((array) $run, ['scheduled_for', 'status', 'attempts', 'started_at', 'finished_at', 'error_code']) : null,
            'last_manual' => $manual ? ['status' => $manual->properties['status'], 'file' => $manual->properties['file'],
                'finished_at' => $manual->created_at->toISOString()] : null];
    }

    public function recordManualBackup(string $status, ?string $file, int $actorId): void
    {
        if (! in_array($status, ['requested', 'succeeded', 'failed'], true)
            || ($file !== null && ! preg_match('/^pos-[0-9]{8}-[0-9]{6}-[a-f0-9]{8}\.sql$/D', $file))) {
            throw new \LogicException('Hasil backup manual tidak valid.');
        }
        $log = activity('platform.operational-settings')->causedBy($actorId)->event('backup.manual.'.$status)
            ->withProperties(['status' => $status, 'file' => $file])->log('Eksekusi backup manual');
        if ($log === null || ! $log->exists) {
            throw new RuntimeException('Audit backup gagal disimpan.');
        }
    }

    public function claimDue(): ?array
    {
        return DB::transaction(function (): ?array {
            $settings = OperationalSetting::query()->lockForUpdate()->findOrFail(self::ID);
            $now = now()->utc();
            DB::table('operational_settings')->where('id', self::ID)->update(['scheduler_seen_at' => $now]);
            $day = $now->copy()->timezone('Asia/Jakarta')->toDateString();
            DB::table('operational_backup_runs')->where('status', 'running')->where('started_at', '<=', $now->copy()->subMinutes(15))
                ->where(fn ($query) => $query->where('scheduled_for', '!=', $day)->orWhere('attempts', '>=', 3))
                ->update(['status' => 'failed', 'error_code' => 'backup_interrupted', 'finished_at' => $now, 'updated_at' => $now]);
            $run = DB::table('operational_backup_runs')->where('scheduled_for', $day)->first();
            if (! BackupWindow::isDue($settings->toArray(), $run ? (array) $run : null, $now->toDateTimeImmutable())) {
                return null;
            }
            if (DB::table('operational_backup_runs')->where('status', 'running')->where('started_at', '>', $now->copy()->subMinutes(15))->exists()) {
                return null;
            }
            $id = $run?->id ?? (string) Str::ulid();
            $token = (string) Str::ulid();
            $values = ['status' => 'running', 'attempts' => ($run?->attempts ?? 0) + 1, 'token' => $token,
                'started_at' => $now, 'finished_at' => null, 'backup_file' => null, 'error_code' => null, 'updated_at' => $now];
            if ($run) {
                DB::table('operational_backup_runs')->where('id', $id)->update($values);
            } else {
                DB::table('operational_backup_runs')->insert($values + ['id' => $id, 'scheduled_for' => $day, 'created_at' => $now]);
            }

            return ['id' => $id, 'token' => $token];
        }, 3);
    }

    public function finish(array $claim, string $status, ?string $file): void
    {
        if (! in_array($status, ['succeeded', 'failed'], true)) {
            throw new \LogicException('Status backup tidak valid.');
        }
        DB::transaction(function () use ($claim, $status, $file): void {
            $changed = DB::table('operational_backup_runs')->where('id', $claim['id'])->where('token', $claim['token'])->where('status', 'running')
                ->update(['status' => $status, 'backup_file' => $status === 'succeeded' ? $file : null,
                    'error_code' => $status === 'failed' ? 'backup_failed' : null, 'finished_at' => now()->utc(), 'updated_at' => now()->utc()]);
            if ($changed) {
                $log = activity('platform.operational-settings')->causedByAnonymous()->event('backup.'.$status)
                    ->withProperties(['run_id' => $claim['id'], 'status' => $status])->log('Eksekusi backup terjadwal');
                if ($log === null || ! $log->exists) {
                    throw new RuntimeException('Audit backup gagal disimpan.');
                }
            }
        }, 3);
    }
}
