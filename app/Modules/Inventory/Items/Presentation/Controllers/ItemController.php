<?php

namespace App\Modules\Inventory\Items\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Items\Application\Actions\CreateItem;
use App\Modules\Inventory\Items\Application\Actions\ManageItem;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Application\DTOs\ItemUnitData;
use App\Modules\Inventory\Items\Application\Queries\ListItems;
use App\Modules\Inventory\Items\Presentation\Requests\StoreItemRequest;
use App\Modules\Inventory\Items\Presentation\Requests\UpdateItemRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:inventory.view', only: ['data']),
            new Middleware('can:inventory.manage', only: ['store', 'update', 'destroy', 'restore']),
        ];
    }

    public function data(Request $request, ListItems $query): JsonResponse
    {
        $input = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:2147483647']]);

        return response()->json($query->handle((int) ($input['page'] ?? 1)));
    }

    public function update(UpdateItemRequest $request, string $id, ManageItem $action): JsonResponse|RedirectResponse
    {
        $input = $request->validated();
        $units = array_map(fn (array $unit): ItemUnitData => new ItemUnitData(
            $unit['unit_id'], $unit['conversion_ratio'], $unit['barcode'] ?? null,
            (bool) ($unit['is_sale_unit'] ?? true), (bool) ($unit['is_purchase_unit'] ?? true),
        ), $input['units'] ?? []);
        try {
            $action->update($id, new CreateItemData($input['sku'], $input['name'], $input['base_unit_id'], $units, (bool) $input['is_active']));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['units' => $exception->getMessage()]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['inventory' => 'SKU atau barcode sudah digunakan, termasuk pada data terhapus.']);
        }

        return $this->completed($request, $id, 'Barang berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id, ManageItem $action): JsonResponse|RedirectResponse
    {
        $action->delete($id);

        return $this->completed($request, $id, 'Barang berhasil dihapus.');
    }

    public function restore(Request $request, string $id, ManageItem $action): JsonResponse|RedirectResponse
    {
        $action->restore($id);

        return $this->completed($request, $id, 'Barang berhasil dipulihkan.');
    }

    private function completed(Request $request, string $id, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson() ? response()->json(['id' => $id]) : back()->with('success', $message);
    }

    public function store(StoreItemRequest $request, CreateItem $action): JsonResponse|RedirectResponse
    {
        $input = $request->validated();
        $units = array_map(fn (array $unit): ItemUnitData => new ItemUnitData(
            $unit['unit_id'], $unit['conversion_ratio'], $unit['barcode'] ?? null,
            (bool) ($unit['is_sale_unit'] ?? true), (bool) ($unit['is_purchase_unit'] ?? true),
        ), $input['units'] ?? []);

        try {
            $id = $action->handle(new CreateItemData($input['sku'], $input['name'], $input['base_unit_id'], $units, $request->boolean('is_active', true)));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['units' => $exception->getMessage()]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['inventory' => 'SKU atau barcode sudah digunakan, termasuk pada data terhapus.']);
        }

        return $request->expectsJson()
            ? response()->json(['id' => $id], 201)
            : back()->with('success', 'Barang berhasil ditambahkan.');
    }
}
