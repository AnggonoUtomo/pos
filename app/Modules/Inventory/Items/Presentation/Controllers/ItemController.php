<?php

namespace App\Modules\Inventory\Items\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Items\Application\Actions\ManageItem;
use App\Modules\Inventory\Items\Application\Actions\SaveItemCatalog;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Application\DTOs\ItemUnitData;
use App\Modules\Inventory\Items\Application\Queries\ListItems;
use App\Modules\Inventory\Items\Presentation\Requests\ListItemsRequest;
use App\Modules\Inventory\Items\Presentation\Requests\StoreItemRequest;
use App\Modules\Inventory\Items\Presentation\Requests\UpdateItemRequest;
use App\Modules\Sales\Parties\Application\DTOs\ItemPriceData;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:inventory.view', only: ['data', 'index']),
            new Middleware('can:inventory.manage', only: ['store', 'update', 'destroy', 'restore']),
        ];
    }

    public function data(Request $request, ListItems $query): JsonResponse
    {
        $input = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:2147483647']]);

        return response()->json($query->handle((int) ($input['page'] ?? 1)));
    }

    public function index(ListItemsRequest $request, ListItems $query): Response
    {
        $filters = $request->filters();

        return Inertia::render('inventory/items/index', [
            'records' => $query->handle((int) $request->validated('page', 1), $filters),
            'filters' => $filters, 'unitOptions' => $query->unitOptions(), 'priceLevels' => $query->priceLevels(),
        ]);
    }

    public function update(UpdateItemRequest $request, string $id, SaveItemCatalog $action): JsonResponse|RedirectResponse
    {
        $input = $request->validated();
        $units = array_map(fn (array $unit): ItemUnitData => new ItemUnitData(
            $unit['unit_id'], $unit['conversion_ratio'], $unit['barcode'] ?? null,
            (bool) ($unit['is_sale_unit'] ?? true), (bool) ($unit['is_purchase_unit'] ?? true),
        ), $input['units'] ?? []);
        try {
            $action->save(new CreateItemData($input['sku'], $input['name'], $input['base_unit_id'], $units, (bool) $input['is_active']), $this->priceData($input), $id);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['units' => $exception->getMessage()]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['inventory' => 'SKU atau barcode sudah digunakan, termasuk pada data terhapus.']);
        }

        return $this->completed($request, $id, 'Barang berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id, ManageItem $action): JsonResponse|RedirectResponse
    {
        try {
            $action->delete($id);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['inventory' => $exception->getMessage()]);
        }

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

    public function store(StoreItemRequest $request, SaveItemCatalog $action): JsonResponse|RedirectResponse
    {
        $input = $request->validated();
        $units = array_map(fn (array $unit): ItemUnitData => new ItemUnitData(
            $unit['unit_id'], $unit['conversion_ratio'], $unit['barcode'] ?? null,
            (bool) ($unit['is_sale_unit'] ?? true), (bool) ($unit['is_purchase_unit'] ?? true),
        ), $input['units'] ?? []);

        try {
            $id = $action->save(new CreateItemData($input['sku'], $input['name'], $input['base_unit_id'], $units, $request->boolean('is_active', true)), $this->priceData($input));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['units' => $exception->getMessage()]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['inventory' => 'SKU atau barcode sudah digunakan, termasuk pada data terhapus.']);
        }

        return $request->expectsJson()
            ? response()->json(['id' => $id], 201)
            : back()->with('success', 'Barang berhasil ditambahkan.');
    }

    private function priceData(array $input): array
    {
        return array_map(fn (array $row) => new ItemPriceData($row['unit_id'], $row['customer_level_id'], $row['amount']), $input['prices'] ?? []);
    }
}
