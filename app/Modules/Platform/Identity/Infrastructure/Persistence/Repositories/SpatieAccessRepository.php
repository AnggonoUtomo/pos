<?php

namespace App\Modules\Platform\Identity\Infrastructure\Persistence\Repositories;

use App\Models\User;
use App\Modules\Platform\Identity\Application\Contracts\AccessRepository;
use Illuminate\Support\Facades\DB;
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
            'users' => User::query()->select(['id', 'name', 'email'])->with(['roles' => fn ($q) => $q->where('guard_name', 'web')])->withCount('permissions'),
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
                'roles' => $row->roles->pluck('name')->sort()->values()->all(), 'direct_permissions_count' => $row->permissions_count],
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

        return ['roles' => $roles->map(fn ($role) => $this->roleData($role))->all(), 'permissions' => $permissions->all()];
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

        return ['super' => $user->hasRole('super-system', 'web'),
            'permissions' => $user->getAllPermissions()->where('guard_name', 'web')->pluck('name')->all()];
    }

    public function lockUser(int $id): array
    {
        $user = User::query()->whereKey($id)->lockForUpdate()->firstOrFail();

        return ['id' => $user->id, 'roles' => $user->roles()->where('guard_name', 'web')->pluck('name')->sort()->values()->all()];
    }

    public function superUserCount(): int
    {
        return User::role('super-system', 'web')->count();
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

    public function clearCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
