<?php

namespace App\Modules\Platform\Identity\Application\Actions;

use App\Models\User;
use Spatie\Permission\Models\Role;

class SyncRolePermissions
{
    public function __construct(private ManageAccess $access) {}

    /**
     * @param  list<string>  $permissionNames
     */
    public function handle(User $actor, Role $role, array $permissionNames): void
    {
        $this->access->syncPermissions((int) $actor->id, (int) $role->id, $permissionNames);
    }
}
