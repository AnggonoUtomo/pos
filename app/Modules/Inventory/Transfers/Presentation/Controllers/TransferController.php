<?php

namespace App\Modules\Inventory\Transfers\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Transfers\Application\Actions\ManageTransferDraft;
use App\Modules\Inventory\Transfers\Application\Actions\PostTransfer;
use App\Modules\Inventory\Transfers\Application\Queries\TransferWorkspace;
use App\Modules\Inventory\Transfers\Presentation\Requests\ListTransfersRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class TransferController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:inventory.transfers.post', only: ['post']), new Middleware('can:inventory.transfers.view', only: ['index', 'data']),
            new Middleware('can:inventory.transfers.manage', only: ['options', 'store', 'update', 'destroy', 'restore'])];
    }

    public function index(ListTransfersRequest $request, TransferWorkspace $query): Response
    {
        $filters = $request->filters();

        return Inertia::render('inventory/transfers/index', ['records' => $query->records((int) $request->validated('page', 1), $filters),
            'filters' => $filters, 'options' => $request->user()->can('inventory.transfers.manage') ? $query->options() : ['items' => [], 'units' => [], 'warehouses' => $query->warehouseChoices()]]);
    }

    public function data(ListTransfersRequest $request, TransferWorkspace $query): JsonResponse
    {
        return response()->json($query->records((int) $request->validated('page', 1), $request->filters()));
    }

    public function options(TransferWorkspace $query): JsonResponse
    {
        return response()->json($query->options());
    }

    public function store(Request $request, ManageTransferDraft $action): JsonResponse|RedirectResponse
    {
        $id = $action->save($request->all());

        return $request->expectsJson() ? response()->json(['id' => $id], 201) : back()->with('success', 'Draft transfer berhasil disimpan.');
    }

    public function update(Request $request, string $id, ManageTransferDraft $action): JsonResponse|RedirectResponse
    {
        $id = $action->save($request->all(), strtolower($id));

        return $request->expectsJson() ? response()->json(['id' => $id]) : back()->with('success', 'Draft transfer berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id, ManageTransferDraft $action): JsonResponse|RedirectResponse
    {
        $action->delete(strtolower($id), $request->all());

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Draft transfer berhasil dihapus.');
    }

    public function restore(Request $request, string $id, ManageTransferDraft $action): JsonResponse|RedirectResponse
    {
        $action->restore(strtolower($id), $request->all());

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Draft transfer berhasil dipulihkan.');
    }

    public function post(Request $request, string $id, PostTransfer $action): JsonResponse|RedirectResponse
    {
        $action->handle(strtolower($id), $request->all(), $request->user()->id);

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Stok berhasil dipindahkan.');
    }
}
