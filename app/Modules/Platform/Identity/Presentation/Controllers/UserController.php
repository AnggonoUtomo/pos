<?php

namespace App\Modules\Platform\Identity\Presentation\Controllers;

use App\Modules\Platform\Identity\Application\Actions\ManageUsers;
use App\Modules\Platform\Identity\Presentation\Requests\UserRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;

class UserController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:platform.identity.users.create', only: ['store']),
            new Middleware('can:platform.identity.users.update', only: ['update'])];
    }

    private function identifier(string $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        abort_if($id === false || $id < 1, 404);

        return $id;
    }

    private function response(Request $request, int $id, int $status = 200)
    {
        return $request->expectsJson() ? response()->json(['id' => $id], $status)
            : back()->with('success', 'Pengguna berhasil disimpan.');
    }

    public function store(UserRequest $request, ManageUsers $action)
    {
        try {
            $id = $action->create((int) $request->user()->id, $request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'Email sudah digunakan.']);
        }

        return $this->response($request, $id, 201);
    }

    public function update(UserRequest $request, ManageUsers $action, string $id)
    {
        $id = $this->identifier($id);
        try {
            $action->update((int) $request->user()->id, $id, $request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'Email sudah digunakan.']);
        }

        return $this->response($request, $id);
    }
}
