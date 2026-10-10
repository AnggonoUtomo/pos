<?php

namespace App\Modules\Inventory\Warehouses\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Warehouses\Application\Actions\CreateWarehouse;
use App\Modules\Inventory\Warehouses\Application\Actions\ManageWarehouse;
use App\Modules\Inventory\Warehouses\Application\DTOs\CreateWarehouseData;
use App\Modules\Inventory\Warehouses\Application\Queries\ListWarehouses;
use App\Modules\Inventory\Warehouses\Presentation\Requests\StoreWarehouseRequest;
use App\Modules\Inventory\Warehouses\Presentation\Requests\UpdateWarehouseRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class WarehouseController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:inventory.view', only: ['data']),
            new Middleware('can:inventory.manage', only: ['store', 'update', 'destroy', 'restore']),
        ];
    }

    public function update(UpdateWarehouseRequest $request, string $id, ManageWarehouse $action): JsonResponse|RedirectResponse
    {
        $input = $request->validated();
        try {
            $action->update($id, new CreateWarehouseData($input['code'], $input['name'], (bool) $input['is_active']));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'Kode sudah digunakan, termasuk pada data terhapus.']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['inventory' => $exception->getMessage()]);
        }

        return $this->completed($request, $id, 'Data berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id, ManageWarehouse $action): JsonResponse|RedirectResponse
    {
        try {
            $action->delete($id);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['inventory' => $exception->getMessage()]);
        }

        return $this->completed($request, $id, 'Data berhasil dihapus.');
    }

    public function restore(Request $request, string $id, ManageWarehouse $action): JsonResponse|RedirectResponse
    {
        $action->restore($id);

        return $this->completed($request, $id, 'Data berhasil dipulihkan.');
    }

    private function completed(Request $request, string $id, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson() ? response()->json(['id' => $id]) : back()->with('success', $message);
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
