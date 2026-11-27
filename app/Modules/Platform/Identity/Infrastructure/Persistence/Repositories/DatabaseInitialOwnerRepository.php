<?php

namespace App\Modules\Platform\Identity\Infrastructure\Persistence\Repositories;

use App\Models\User;
use App\Modules\Platform\Identity\Application\Contracts\InitialOwnerRepository;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DatabaseInitialOwnerRepository implements InitialOwnerRepository
{
    public function lockSystemRole(): bool
    {
        return Role::query()->where('name', 'super-system')->where('guard_name', 'web')->lockForUpdate()->first() !== null;
    }

    public function isPristine(): bool
    {
        $catalog = array_merge(['migrations', 'cache', 'cache_locks'], array_intersect_key(
            config('permission.table_names'), array_flip(['roles', 'permissions', 'role_has_permissions'])
        ));
        // Read-only setup inspection, never a cross-module business mutation.
        $schema = DB::connection()->getSchemaBuilder();
        foreach ($schema->getTables($schema->getCurrentSchemaListing()) as $table) {
            $name = $table['name'];
            if (in_array($name, $catalog, true)) {
                continue;
            }
            if ($name === config('activitylog.table_name', 'activity_log')) {
                if (DB::table($name)->where(function ($query): void {
                    $query->whereNull('event')->orWhere('event', '!=', 'identity.production_role_created')
                        ->orWhereNotNull('causer_id')->orWhereNull('subject_type')->orWhere('subject_type', '!=', Role::class);
                })->lockForUpdate()->first() !== null) {
                    return false;
                }

                continue;
            }
            if ($name === 'operational_settings') {
                $defaults = DB::table($name)->lockForUpdate()->get();
                if ($defaults->count() !== 1) {
                    return false;
                }
                $row = $defaults->sole();
                if ($row->id !== '00000000000000000000000002' || $row->receipt_paper !== 'a4'
                    || (bool) $row->backup_enabled || $row->backup_time !== '21:00' || (int) $row->version !== 0
                    || (int) $row->table_per_page !== 10 || $row->scheduler_seen_at !== null) {
                    return false;
                }

                continue;
            }
            if (DB::table($name)->selectRaw('1')->lockForUpdate()->first() !== null) {
                return false;
            }
        }

        return true;
    }

    public function create(#[\SensitiveParameter] array $profile): int
    {
        $user = User::create($profile);
        $user->assignRole(Role::findByName('super-system', 'web'));

        return $user->id;
    }

    public function audit(int $id): void
    {
        $user = User::findOrFail($id);
        activity('identity')->performedOn($user)->causedBy($user)
            ->event('identity.initial_owner_created')
            ->withProperties(['source' => 'standalone-setup', 'roles' => ['super-system']])
            ->log('Initial owner created');
    }
}
