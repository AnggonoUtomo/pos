<?php

namespace App\Modules\Platform\Identity\Application\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class SyncRolePermissions
{
    /**
     * @param  list<string>  $permissionNames
     */
    public function handle(User $actor, Role $role, array $permissionNames): void
    {
        $before = $this->permissionNames($role);
        $after = $this->normalize($permissionNames);

        DB::transaction(function () use ($actor, $role, $before, $after): void {
            $role->syncPermissions($after);

            activity('identity')
                ->causedBy($actor)
                ->performedOn($role)
                ->event('identity.role_permissions_synced')
                ->withProperties([
                    'before' => ['permissions' => $before],
                    'after' => ['permissions' => $after],
                ])
                ->log('Role permissions synchronized');
        });
    }

    /**
     * @return list<string>
     */
    private function permissionNames(Role $role): array
    {
        return $role->permissions()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function normalize(array $values): array
    {
        return collect($values)
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
