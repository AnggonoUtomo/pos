<?php

namespace App\Modules\Platform\Identity\Application\Actions;

use App\Models\User;

class SyncUserRoles
{
    public function __construct(private ManageAccess $access) {}

    /**
     * @param  list<string>  $roleNames
     */
    public function handle(User $actor, User $user, array $roleNames): void
    {
        $this->access->syncRoles((int) $actor->id, (int) $user->id, $roleNames);
    }
}
