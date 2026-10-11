<?php

namespace App\Modules\Platform\Identity\Infrastructure\Persistence\Repositories;

use App\Models\User;
use App\Modules\Platform\Identity\Application\Contracts\AccessRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SpatieAccessRepository implements AccessRepository
{
    private function roleData(Role $role): array
    {
        return ['id' => $role->id, 'name' => $role->name, 'users_count' => (int) $role->users_count,
            'permissions' => $role->permissions->where('guard_name', 'web')->pluck('name')->sort()->values()->all()];
    }

    public function page(string $tab, string $search, int $page): array
    {
        $query = match ($tab) {
            'users' => User::query()->select(['id', 'name', 'email', 'is_active'])
                ->with(['roles.permissions', 'permissions'])->withCount('permissions'),
            'permissions' => Permission::query()->where('guard_name', 'web')->withCount(['roles' => fn ($q) => $q->where('guard_name', 'web')]),
            default => Role::query()->where('guard_name', 'web')->with('permissions')->withCount('users'),
        };
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')
                ->when($tab === 'users', fn ($q) => $q->orWhere('email', 'like', '%'.$search.'%')));
        }
        $paginator = $query->orderBy('name')->orderBy('id')->paginate(25, page: $page);
        $data = $paginator->getCollection()->map(fn ($row) => match ($tab) {
            'users' => ['id' => $row->id, 'name' => $row->name, 'email' => $row->email,
                'roles' => $row->roles->where('guard_name', 'web')->pluck('name')->sort()->values()->all(), 'direct_permissions_count' => $row->permissions_count,
                'is_active' => $row->is_active, 'can_edit' => $this->editableByCurrentUser($row)],
            'permissions' => ['id' => $row->id, 'name' => $row->name, 'roles_count' => $row->roles_count],
            default => $this->roleData($row),
        })->all();

        return ['data' => $data, 'meta' => ['current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]];
    }

    public function options(): array
    {
        $roles = Role::query()->where('guard_name', 'web')->with('permissions')->withCount('users')->orderBy('name')->limit(1001)->get();
        $permissions = Permission::query()->where('guard_name', 'web')->orderBy('name')->limit(1001)->pluck('name');
        if ($roles->count() > 1000 || $permissions->count() > 1000) {
            throw ValidationException::withMessages(['access' => 'Katalog akses melebihi 1000 pilihan; diperlukan pencarian remote.']);
        }

        return ['roles' => $roles->map(fn ($role) => $this->roleData($role))->all(), 'permissions' => $permissions->all(),
            'active_super_users' => $this->superUserCount()];
    }

    public function lockRoles(): array
    {
        // Acquire serialization locks before consistent reads of permissions/users.
        $roles = Role::query()->where('guard_name', 'web')->orderBy('id')->lockForUpdate()->get();
        $roles->load('permissions');

        return $roles->map(fn ($role) => $this->roleData($role))->all();
    }

    public function actor(int $id): array
    {
        $user = User::findOrFail($id);

        return ['super' => $user->is_active && $user->hasRole('super-system', 'web'), 'active' => $user->is_active,
            'permissions' => $user->getAllPermissions()->where('guard_name', 'web')->pluck('name')->all()];
    }

    public function lockUser(int $id): array
    {
        $user = User::query()->whereKey($id)->lockForUpdate()->firstOrFail();

        return ['id' => $user->id, 'is_active' => $user->is_active,
            'roles' => $user->roles()->where('guard_name', 'web')->pluck('name')->sort()->values()->all()];
    }

    public function superUserCount(): int
    {
        return User::query()->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'super-system')->where('guard_name', 'web'))->count();
    }

    public function roleReferences(int $id): int
    {
        return DB::table(config('permission.table_names.model_has_roles'))->where('role_id', $id)->count();
    }

    public function createRole(string $name): int
    {
        return Role::create(['name' => $name, 'guard_name' => 'web'])->id;
    }

    public function renameRole(int $id, string $name): void
    {
        Role::findOrFail($id)->update(['name' => $name]);
    }

    public function deleteRole(int $id): void
    {
        Role::findOrFail($id)->delete();
    }

    public function syncPermissions(int $id, array $names): void
    {
        Role::findOrFail($id)->syncPermissions($names);
    }

    public function syncRoles(int $id, array $names): void
    {
        $user = User::findOrFail($id);
        // Keep roles owned by other guards unchanged.
        $other = $user->roles()->where('guard_name', '!=', 'web')->get();
        $selected = Role::where('guard_name', 'web')->whereIn('name', $names)->get();
        $user->roles()->sync($other->merge($selected)->modelKeys());
    }

    public function audit(string $event, int $actorId, string $subject, int $id, array $before, array $after): void
    {
        $model = $subject === 'role' ? Role::findOrFail($id) : User::findOrFail($id);
        activity('identity')->causedBy(User::findOrFail($actorId))->performedOn($model)
            ->event($event)->withProperties(['before' => $before, 'after' => $after])->log('Identity access changed');
    }

    public function deleteUser(int $id): void
    {
        if (! User::findOrFail($id)->delete()) {
            throw new \RuntimeException('Penghapusan akun dibatalkan.');
        }
    }

    public function clearCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function userProfile(int $id): array
    {
        $user = User::findOrFail($id);

        return ['name' => $user->name, 'email' => $user->email, 'is_active' => $user->is_active,
            'super' => $user->hasRole('super-system', 'web'),
            'other_guards' => $user->roles()->where('guard_name', '!=', 'web')->exists()
                || $user->permissions()->where('guard_name', '!=', 'web')->exists(),
            'permissions' => $user->getAllPermissions()->where('guard_name', 'web')->pluck('name')->all()];
    }

    public function emailExists(string $email, ?int $except = null): bool
    {
        return User::query()->whereRaw('LOWER(email) = ?', [$email])
            ->when($except !== null, fn ($query) => $query->where('id', '!=', $except))->exists();
    }

    public function createUser(string $name, string $email, string $password): int
    {
        return User::create(['name' => $name, 'email' => $email, 'password' => $password])->id;
    }

    public function updateUser(int $id, string $name, string $email): void
    {
        $user = User::findOrFail($id);
        if ($user->email !== $email) {
            DB::table('password_reset_tokens')->whereIn('email', [$user->email, $email])->delete();
            $user->email_verified_at = null;
            $user->remember_token = Str::random(60);
        }
        $user->name = $name;
        $user->email = $email;
        if (! $user->save()) {
            throw new \RuntimeException('Perubahan akun dibatalkan.');
        }
    }

    public function setUserStatus(int $id, bool $active): void
    {
        $user = User::findOrFail($id);
        $user->is_active = $active;
        if (! $active) {
            $user->access_version++;
            $user->remember_token = Str::random(60);
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        }
        if (! $user->save()) {
            throw new \RuntimeException('Perubahan status akun dibatalkan.');
        }
    }

    public function sessionState(int $id): ?array
    {
        $user = User::query()->select(['id', 'is_active', 'access_version'])->find($id);

        return $user ? ['active' => $user->is_active, 'version' => $user->access_version] : null;
    }

    private function editableByCurrentUser(User $target): bool
    {
        $actor = auth()->user();
        if (! $actor) {
            return false;
        }
        if ($actor->hasRole('super-system', 'web')) {
            return true;
        }

        return ! $target->hasRole('super-system', 'web')
            && ! $target->roles->contains(fn ($role) => $role->guard_name !== 'web')
            && ! $target->permissions->contains(fn ($permission) => $permission->guard_name !== 'web')
            && array_diff($target->getAllPermissions()->where('guard_name', 'web')->pluck('name')->all(),
                $actor->getAllPermissions()->where('guard_name', 'web')->pluck('name')->all()) === [];
    }
}
