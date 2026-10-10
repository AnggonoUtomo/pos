<?php

namespace App\Modules\Inventory\Units\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Units\Application\Actions\CreateUnit;
use App\Modules\Inventory\Units\Application\Actions\ManageUnit;
use App\Modules\Inventory\Units\Application\DTOs\CreateUnitData;
use App\Modules\Inventory\Units\Application\Queries\ListUnits;
use App\Modules\Inventory\Units\Presentation\Requests\StoreUnitRequest;
use App\Modules\Inventory\Units\Presentation\Requests\UpdateUnitRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class UnitController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:inventory.view', only: ['data']),
            new Middleware('can:inventory.manage', only: ['store', 'update', 'destroy', 'restore']),
        ];
    }

    public function update(UpdateUnitRequest $request, string $id, ManageUnit $action): JsonResponse|RedirectResponse
    {
        $input = $request->validated();
        try {
            $action->update($id, new CreateUnitData($input['code'], $input['name'], $input['symbol'], (bool) $input['is_active']));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'Kode sudah digunakan, termasuk pada data terhapus.']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['inventory' => $exception->getMessage()]);
        }

        return $this->completed($request, $id, 'Data berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id, ManageUnit $action): JsonResponse|RedirectResponse
    {
        try {
            $action->delete($id);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['inventory' => $exception->getMessage()]);
        }

        return $this->completed($request, $id, 'Data berhasil dihapus.');
    }

    public function restore(Request $request, string $id, ManageUnit $action): JsonResponse|RedirectResponse
    {
        $action->restore($id);

        return $this->completed($request, $id, 'Data berhasil dipulihkan.');
    }

    private function completed(Request $request, string $id, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson() ? response()->json(['id' => $id]) : back()->with('success', $message);
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
