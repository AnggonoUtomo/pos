<?php

namespace App\Modules\Platform\CompanySettings\Infrastructure\Persistence\Repositories;

use App\Modules\Platform\CompanySettings\Application\Contracts\CompanyProfile;
use App\Modules\Platform\CompanySettings\Application\Contracts\CompanySettingsRepository;
use App\Modules\Platform\CompanySettings\Infrastructure\Persistence\Models\CompanySetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class DatabaseCompanySettings implements CompanyProfile, CompanySettingsRepository
{
    private const ID = '00000000000000000000000001';

    private const FIELDS = ['name', 'address', 'phone', 'receipt_footer', 'version'];

    public function read(): array
    {
        $model = CompanySetting::find(self::ID);

        return $model ? Arr::only($model->toArray(), self::FIELDS) : ['name' => '', 'address' => '', 'phone' => '', 'receipt_footer' => '', 'version' => 0];
    }

    public function update(array $fields, int $version, ?int $actorId): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Update pengaturan memerlukan transaction.');
        }
        DB::table('company_settings')->insertOrIgnore(['id' => self::ID, 'name' => '', 'address' => '', 'phone' => '', 'receipt_footer' => '', 'version' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $model = CompanySetting::query()->lockForUpdate()->findOrFail(self::ID);
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
        $logger = activity('platform.company-settings')->performedOn($model)->event('updated')->withProperties(['version' => $model->version]);
        $actorId === null ? $logger->causedByAnonymous() : $logger->causedBy($actorId);
        $log = $logger->log('Pengaturan toko diperbarui');
        if ($log === null || ! $log->exists) {
            throw new RuntimeException('Audit pengaturan gagal disimpan.');
        }
    }
}
