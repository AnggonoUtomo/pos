<?php

namespace App\Modules\Platform\Identity\Presentation\Controllers;

use App\Modules\Platform\Identity\Application\Actions\ManageAccess;
use App\Modules\Platform\Identity\Application\Queries\ListAccess;
use App\Modules\Platform\Identity\Presentation\Requests\AccessRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AccessController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:platform.identity.manage', except: ['index'])];
    }

    public function index(AccessRequest $request, ListAccess $query)
    {
        $input = $request->validated();
        $fullAccess = $request->user()->can('platform.identity.view');
        $tab = $input['tab'] ?? ($fullAccess ? 'roles' : 'users');
        Gate::authorize($fullAccess || $tab !== 'users' ? 'platform.identity.view' : 'platform.identity.users.view');

        return Inertia::render('platform/identity/index', $query->handle([
            'tab' => $tab, 'search' => $input['search'] ?? '', 'page' => (int) ($input['page'] ?? 1)]));
    }

    private function response(Request $request, int $id, int $status = 200)
    {
        return $request->expectsJson() ? response()->json(['id' => $id], $status) : back()->with('success', 'Akses berhasil diperbarui.');
    }

    public function store(AccessRequest $request, ManageAccess $action)
    {
        return $this->save($request, $action, null);
    }

    private function identifier(string $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        abort_if($id === false || $id < 1, 404);

        return $id;
    }

    public function update(AccessRequest $request, ManageAccess $action, string $id)
    {
        return $this->save($request, $action, $this->identifier($id));
    }

    private function save(AccessRequest $request, ManageAccess $action, ?int $id)
    {
        try {
            $result = $action->saveRole((int) $request->user()->id, $id, $request->validated('name'));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => 'Nama role sudah digunakan.']);
        }

        return $this->response($request, $result, $id ? 200 : 201);
    }

    public function destroy(Request $request, ManageAccess $action, string $id)
    {
        $id = $this->identifier($id);
        $action->deleteRole((int) $request->user()->id, $id);

        return $this->response($request, $id);
    }

    public function permissions(AccessRequest $request, ManageAccess $action, string $id)
    {
        $id = $this->identifier($id);
        $action->syncPermissions((int) $request->user()->id, $id, $request->validated('permissions'));

        return $this->response($request, $id);
    }

    public function roles(AccessRequest $request, ManageAccess $action, string $id)
    {
        $id = $this->identifier($id);
        $action->syncRoles((int) $request->user()->id, $id, $request->validated('roles'));

        return $this->response($request, $id);
    }
}
