<?php

namespace App\Modules\Purchasing\Suppliers\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchasing\Suppliers\Application\Actions\ManageSupplier;
use App\Modules\Purchasing\Suppliers\Application\DTOs\SupplierData;
use App\Modules\Purchasing\Suppliers\Application\Queries\ListSuppliers;
use App\Modules\Purchasing\Suppliers\Presentation\Requests\ListSuppliersRequest;
use App\Modules\Purchasing\Suppliers\Presentation\Requests\SaveSupplierRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:purchasing.suppliers.view', only: ['index', 'data']),
            new Middleware('can:purchasing.suppliers.manage', only: ['store', 'update', 'destroy', 'restore'])];
    }

    public function index(ListSuppliersRequest $request, ListSuppliers $query): Response
    {
        $filters = $request->filters();

        return Inertia::render('purchasing/suppliers/index', ['records' => $query->handle((int) $request->validated('page', 1), $filters), 'filters' => $filters]);
    }

    public function data(ListSuppliersRequest $request, ListSuppliers $query): JsonResponse
    {
        return response()->json($query->handle((int) $request->validated('page', 1), $request->filters()));
    }

    public function store(SaveSupplierRequest $request, ManageSupplier $action): JsonResponse|RedirectResponse
    {
        return $this->save($request, $action);
    }

    public function update(SaveSupplierRequest $request, string $id, ManageSupplier $action): JsonResponse|RedirectResponse
    {
        return $this->save($request, $action, strtolower($id));
    }

    private function save(SaveSupplierRequest $request, ManageSupplier $action, ?string $id = null): JsonResponse|RedirectResponse
    {
        $input = $request->validated();
        try {
            $saved = $action->save(new SupplierData($input['code'], $input['name'], $input['phone'] ?? null,
                $input['email'] ?? null, $input['address'] ?? null, $request->boolean('is_active', true)), $id);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'Kode sudah digunakan, termasuk pada data terhapus.']);
        }

        return $request->expectsJson() ? response()->json(['id' => $saved], $id === null ? 201 : 200) : back()->with('success', 'Supplier berhasil disimpan.');
    }

    public function destroy(Request $request, string $id, ManageSupplier $action): JsonResponse|RedirectResponse
    {
        $action->delete(strtolower($id));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Supplier berhasil dihapus.');
    }

    public function restore(Request $request, string $id, ManageSupplier $action): JsonResponse|RedirectResponse
    {
        $action->restore(strtolower($id));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Supplier berhasil dipulihkan.');
    }
}
