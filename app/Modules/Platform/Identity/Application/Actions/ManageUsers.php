<?php

namespace App\Modules\Platform\Identity\Application\Actions;

use App\Modules\Platform\Identity\Application\Contracts\AccessRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManageUsers
{
    public function __construct(private AccessRepository $access, private ManageAccess $roles) {}

    private function authorize(int $actorId, string $operation, ?int $targetId = null): array
    {
        $actor = $this->access->actor($actorId);
        if (! $actor['super'] && ! in_array('platform.identity.users.'.$operation, $actor['permissions'], true)) {
            throw new AuthorizationException;
        }
        if ($targetId !== null) {
            $target = $this->access->userProfile($targetId);
            if (! $actor['super'] && ($target['super'] || $target['other_guards']
                || array_diff($target['permissions'], $actor['permissions']) !== [])) {
                throw new AuthorizationException;
            }
        }

        return $actor;
    }

    private function profile(array $input, ?int $id = null): array
    {
        $input['name'] = is_string($input['name'] ?? null) ? trim($input['name']) : null;
        $input['email'] = is_string($input['email'] ?? null) ? strtolower(trim($input['email'])) : null;
        $data = Validator::make($input, ['name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255']])->validate();
        if ($this->access->emailExists($data['email'], $id)) {
            throw ValidationException::withMessages(['email' => 'Email sudah digunakan.']);
        }

        return $data;
    }

    public function create(int $actorId, array $input): int
    {
        $id = DB::transaction(function () use ($actorId, $input): int {
            $this->access->lockRoles();
            $this->authorize($actorId, 'create');
            $data = $this->profile($input);
            $credential = Validator::make($input, ['password' => ['required', 'string', 'min:12', 'max:72', 'confirmed'],
                'roles' => ['present', 'array', 'list', 'max:1000'], 'roles.*' => ['required', 'string', 'max:150', 'distinct']])->validate();
            if (in_array('super-admin', array_map('trim', $credential['roles']), true)) {
                throw ValidationException::withMessages(['roles' => 'Role legacy tidak dapat diberikan ke akun baru.']);
            }
            $id = $this->access->createUser($data['name'], $data['email'], $credential['password']);
            if ($credential['roles'] !== []) {
                $this->roles->syncRoles($actorId, $id, $credential['roles']);
            }
            $this->access->audit('identity.user_created', $actorId, 'user', $id, [],
                ['roles' => $this->access->lockUser($id)['roles']]);

            return $id;
        });
        $this->access->clearCache();

        return $id;
    }

    public function update(int $actorId, int $id, array $input): void
    {
        DB::transaction(function () use ($actorId, $id, $input): void {
            $this->access->lockRoles();
            $this->access->lockUser($id);
            $this->authorize($actorId, 'update', $id);
            $before = $this->access->userProfile($id);
            $data = $this->profile($input, $id);
            $changed = array_values(array_filter(['name', 'email'], fn ($field) => $before[$field] !== $data[$field]));
            if ($changed === []) {
                return;
            }
            $this->access->updateUser($id, $data['name'], $data['email']);
            $this->access->audit('identity.user_updated', $actorId, 'user', $id, [], ['changed_fields' => $changed]);
        });
    }
}
