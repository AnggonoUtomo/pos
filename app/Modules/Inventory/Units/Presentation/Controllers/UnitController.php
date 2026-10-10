<?php

namespace App\Modules\Inventory\Units\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Units\Application\Actions\CreateUnit;
use App\Modules\Inventory\Units\Application\DTOs\CreateUnitData;
use App\Modules\Inventory\Units\Application\Queries\ListUnits;
use App\Modules\Inventory\Units\Presentation\Requests\StoreUnitRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;

class UnitController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:inventory.view', only: ['data']),
            new Middleware('can:inventory.manage', only: ['store']),
        ];
    }

    public function data(Request $request, ListUnits $query): JsonResponse
    {
        $input = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:2147483647']]);

        return response()->json($query->handle((int) ($input['page'] ?? 1)));
    }

    public function store(StoreUnitRequest $request, CreateUnit $action): JsonResponse|RedirectResponse
    {
        $input = $request->validated();
        try {
            $id = $action->handle(new CreateUnitData($input['code'], $input['name'], $input['symbol'], $request->boolean('is_active', true)));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'Kode sudah digunakan, termasuk pada data terhapus.']);
        }

        return $request->expectsJson()
            ? response()->json(['id' => $id], 201)
            : back()->with('success', 'Satuan berhasil ditambahkan.');
    }
}
