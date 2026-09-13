<?php

namespace App\Modules\Platform\Identity\Application\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SyncUserRoles
{
    /**
     * @param  list<string>  $roleNames
     */
    public function handle(User $actor, User $user, array $roleNames): void
    {
        $before = $this->roleNames($user);
        $after = $this->normalize($roleNames);

        DB::transaction(function () use ($actor, $user, $before, $after): void {
            $user->syncRoles($after);

            activity('identity')
                ->causedBy($actor)
                ->performedOn($user)
                ->event('identity.user_roles_synced')
                ->withProperties([
                    'before' => ['roles' => $before],
                    'after' => ['roles' => $after],
                ])
                ->log('User roles synchronized');
        });
    }

    /**
     * @return list<string>
     */
    private function roleNames(User $user): array
    {
        return $user->roles()
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
