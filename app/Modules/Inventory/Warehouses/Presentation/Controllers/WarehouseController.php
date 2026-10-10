<?php

namespace App\Modules\Inventory\Warehouses\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Warehouses\Application\Actions\CreateWarehouse;
use App\Modules\Inventory\Warehouses\Application\DTOs\CreateWarehouseData;
use App\Modules\Inventory\Warehouses\Application\Queries\ListWarehouses;
use App\Modules\Inventory\Warehouses\Presentation\Requests\StoreWarehouseRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;

class WarehouseController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:inventory.view', only: ['data']),
            new Middleware('can:inventory.manage', only: ['store']),
        ];
    }

    public function data(Request $request, ListWarehouses $query): JsonResponse
    {
        $input = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:2147483647']]);

        return response()->json($query->handle((int) ($input['page'] ?? 1)));
    }

    public function store(StoreWarehouseRequest $request, CreateWarehouse $action): JsonResponse|RedirectResponse
    {
        $input = $request->validated();
        try {
            $id = $action->handle(new CreateWarehouseData($input['code'], $input['name'], $request->boolean('is_active', true)));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'Kode sudah digunakan, termasuk pada data terhapus.']);
        }

        return $request->expectsJson()
            ? response()->json(['id' => $id], 201)
            : back()->with('success', 'Gudang berhasil ditambahkan.');
    }
}
