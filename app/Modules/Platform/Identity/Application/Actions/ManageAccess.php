<?php

namespace App\Modules\Platform\Identity\Application\Actions;

use App\Modules\Platform\Identity\Application\Contracts\AccessRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManageAccess
{
    public function __construct(private AccessRepository $access) {}

    private function authorize(int $actorId): array
    {
        $actor = $this->access->actor($actorId);
        if (! $actor['super'] && ! in_array('platform.identity.manage', $actor['permissions'], true)) {
            throw new AuthorizationException;
        }

        return $actor;
    }

    private function role(array $roles, int $id): array
    {
        foreach ($roles as $role) {
            if ($role['id'] === $id) {
                return $role;
            }
        }
        throw new ModelNotFoundException;
    }

    private function editable(array $role): void
    {
        if (in_array(strtolower($role['name']), ['super-system', 'super-admin'], true)) {
            throw ValidationException::withMessages(['access' => 'Role sistem tidak dapat diubah melalui CRUD.']);
        }
    }

    private function name(string $name): string
    {
        $name = strtolower(trim($name));
        Validator::make(['name' => $name], ['name' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_-]*$/']])->validate();
        if (in_array($name, ['super-system', 'super-admin'], true)) {
            throw ValidationException::withMessages(['name' => 'Nama role sistem direservasi.']);
        }

        return $name;
    }

    private function names(array $names, string $field, array $allowed): array
    {
        Validator::make([$field => $names], [$field => ['present', 'array', 'list', 'max:1000'],
            $field.'.*' => ['required', 'string', 'max:150', 'distinct']])->validate();
        $names = array_map('trim', $names);
        if (count(array_unique($names)) !== count($names) || array_diff($names, $allowed) !== []) {
            throw ValidationException::withMessages([$field => 'Pilihan akses tidak valid atau berulang.']);
        }
        sort($names);

        return $names;
    }

    private function grant(array $actor, array $permissions): void
    {
        if (! $actor['super'] && array_diff($permissions, $actor['permissions']) !== []) {
            throw new AuthorizationException;
        }
    }

    public function saveRole(int $actorId, ?int $id, string $name): int
    {
        $name = $this->name($name);
        $result = DB::transaction(function () use ($actorId, $id, $name): int {
            $roles = $this->access->lockRoles();
            $actor = $this->authorize($actorId);
            $before = $id ? $this->role($roles, $id) : null;
            if ($before) {
                $this->editable($before);
            }
            foreach ($roles as $role) {
                if (strtolower($role['name']) === $name && $role['id'] !== $id) {
                    throw ValidationException::withMessages(['name' => 'Nama role sudah digunakan.']);
                }
            }
            if ($before && $before['name'] === $name) {
                return $id;
            }
            if ($id) {
                $this->grant($actor, $before['permissions']);
                $this->access->renameRole($id, $name);
            } else {
                $id = $this->access->createRole($name);
            }
            $this->access->audit($before ? 'identity.role_updated' : 'identity.role_created', $actorId, 'role', $id,
                $before ? ['name' => $before['name']] : [], ['name' => $name]);

            return $id;
        });
        $this->access->clearCache();

        return $result;
    }

    public function deleteRole(int $actorId, int $id): void
    {
        DB::transaction(function () use ($actorId, $id): void {
            $roles = $this->access->lockRoles();
            $actor = $this->authorize($actorId);
            $role = $this->role($roles, $id);
            $this->editable($role);
            $this->grant($actor, $role['permissions']);
            if ($this->access->roleReferences($id) > 0) {
                throw ValidationException::withMessages(['access' => 'Role masih digunakan pengguna dan tidak dapat dihapus.']);
            }
            $this->access->audit('identity.role_deleted', $actorId, 'role', $id, ['name' => $role['name'], 'permissions' => $role['permissions']], []);
            $this->access->deleteRole($id);
        });
        $this->access->clearCache();
    }

    public function syncPermissions(int $actorId, int $id, array $names): void
    {
        DB::transaction(function () use ($actorId, $id, $names): void {
            $roles = $this->access->lockRoles();
            $actor = $this->authorize($actorId);
            $role = $this->role($roles, $id);
            $this->editable($role);
            $after = $this->names($names, 'permissions', $this->access->options()['permissions']);
            $this->grant($actor, array_unique([...$role['permissions'], ...$after]));
            if ($after === $role['permissions']) {
                return;
            }
            $this->access->syncPermissions($id, $after);
            $this->access->audit('identity.role_permissions_synced', $actorId, 'role', $id,
                ['permissions' => $role['permissions']], ['permissions' => $after]);
        });
        $this->access->clearCache();
    }

    public function syncRoles(int $actorId, int $id, array $names): void
    {
        DB::transaction(function () use ($actorId, $id, $names): void {
            $roles = $this->access->lockRoles();
            $actor = $this->authorize($actorId);
            $user = $this->access->lockUser($id);
            $after = $this->names($names, 'roles', array_column($roles, 'name'));
            $hasSuper = in_array('super-system', $user['roles'], true);
            $willSuper = in_array('super-system', $after, true);
            if (! $actor['super'] && ($hasSuper || $willSuper)) {
                throw new AuthorizationException;
            }
            if ($hasSuper && ! $willSuper && $this->access->superUserCount() <= 1) {
                throw ValidationException::withMessages(['roles' => 'Minimal satu pengguna super-system harus tetap tersedia.']);
            }
            foreach ($roles as $role) {
                if (in_array($role['name'], $after, true)) {
                    $this->grant($actor, $role['permissions']);
                }
            }
            if ($after === $user['roles']) {
                return;
            }
            $this->access->syncRoles($id, $after);
            $this->access->audit('identity.user_roles_synced', $actorId, 'user', $id, ['roles' => $user['roles']], ['roles' => $after]);
        });
        $this->access->clearCache();
    }
}
