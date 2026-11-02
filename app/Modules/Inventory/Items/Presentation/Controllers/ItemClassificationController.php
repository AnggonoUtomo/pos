<?php

namespace App\Modules\Inventory\Items\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Items\Application\Actions\ManageItemClassification;
use App\Modules\Inventory\Items\Application\Enums\ClassificationKind;
use App\Modules\Inventory\Items\Application\Queries\ListItemClassifications;
use App\Modules\Inventory\Items\Presentation\Requests\ListItemsRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ItemClassificationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:inventory.view', only: ['index']),
            new Middleware('can:inventory.manage', only: ['store', 'update', 'destroy', 'restore'])];
    }

    public function index(ListItemsRequest $request, ListItemClassifications $query): Response
    {
        $kind = ClassificationKind::from($request->route('kind'));

        return Inertia::render('inventory/items/'.$kind->value, ['records' => $query->handle($kind, (int) $request->validated('page', 1), $request->filters()), 'filters' => $request->filters()]);
    }

    public function store(Request $request, ManageItemClassification $action): JsonResponse|RedirectResponse
    {
        return $this->save($request, $action);
    }

    public function update(Request $request, string $id, ManageItemClassification $action): JsonResponse|RedirectResponse
    {
        return $this->save($request, $action, $id);
    }

    private function save(Request $request, ManageItemClassification $action, ?string $id = null): JsonResponse|RedirectResponse
    {
        try {
            $result = $action->save(ClassificationKind::from($request->route('kind')), $request->only(['code', 'name', 'is_active']), $id);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'Kode sudah digunakan, termasuk pada data terhapus.']);
        }

        return $request->expectsJson() ? response()->json(['id' => $result], $id === null ? 201 : 200) : back()->with('success', 'Master berhasil disimpan.');
    }

    public function destroy(Request $request, string $id, ManageItemClassification $action): JsonResponse|RedirectResponse
    {
        $action->delete(ClassificationKind::from($request->route('kind')), $id);

        return $this->completed($request, $id);
    }

    public function restore(Request $request, string $id, ManageItemClassification $action): JsonResponse|RedirectResponse
    {
        $action->restore(ClassificationKind::from($request->route('kind')), $id);

        return $this->completed($request, $id);
    }

    private function completed(Request $request, string $id): JsonResponse|RedirectResponse
    {
        return $request->expectsJson() ? response()->json(['id' => $id]) : back()->with('success', 'Master berhasil diperbarui.');
    }
}
