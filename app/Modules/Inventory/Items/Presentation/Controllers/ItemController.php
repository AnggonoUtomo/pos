<?php

namespace App\Modules\Inventory\Items\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Items\Application\Actions\CreateItem;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Application\DTOs\ItemUnitData;
use App\Modules\Inventory\Items\Application\Queries\ListItems;
use App\Modules\Inventory\Items\Presentation\Requests\StoreItemRequest;
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
            new Middleware('can:inventory.manage', only: ['store']),
        ];
    }

    public function data(Request $request, ListItems $query): JsonResponse
    {
        $input = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:2147483647']]);

        return response()->json($query->handle((int) ($input['page'] ?? 1)));
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
