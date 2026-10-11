<?php

namespace App\Modules\Platform\Identity\Application\Actions;

use DomainException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MigrateLegacySuperRole
{
    public function preview(): bool
    {
        return Role::query()->where('guard_name', 'web')->where('name', 'super-admin')->exists();
    }

    public function handle(): bool
    {
        $changed = DB::transaction(function (): bool {
            $roles = Role::query()->where('guard_name', 'web')
                ->whereIn('name', ['super-admin', 'super-system'])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('name');
            $legacy = $roles->get('super-admin');
            if (! $legacy) {
                return false;
            }
            if ($roles->has('super-system')) {
                throw new DomainException('Kedua role sudah ada. Migrasi dihentikan; perlu keputusan eksplisit untuk konflik akses.');
            }

            $legacy->name = 'super-system';
            $legacy->save();
            activity('identity')->performedOn($legacy)
                ->event('identity.legacy_super_role_migrated')
                ->withProperties([
                    'before' => ['name' => 'super-admin', 'guard_name' => 'web'],
                    'after' => ['name' => 'super-system', 'guard_name' => 'web'],
                    'source' => 'identity:migrate-super-role',
                ])->log('Legacy super role migrated');

            return true;
        });

        if ($changed) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return $changed;
    }
}
