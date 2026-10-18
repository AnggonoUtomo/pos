<?php

namespace App\Modules\Sales\Returns\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Returns\Application\Actions\ManageReturnDraft;
use App\Modules\Sales\Returns\Application\Queries\ReturnWorkspace;
use App\Modules\Sales\Returns\Presentation\Requests\ListReturnDraftsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class ReturnDraftController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:sales.returns.view', only: ['index', 'data']),
            new Middleware('can:sales.returns.manage', only: ['invoices', 'invoice', 'store', 'update', 'destroy', 'restore'])];
    }

    public function index(ListReturnDraftsRequest $request, ReturnWorkspace $query): Response
    {
        $filters = $request->filters();

        return Inertia::render('sales/returns/index', ['records' => $query->records((int) $request->validated('page', 1), ...array_values($filters)), 'filters' => $filters]);
    }

    public function data(ListReturnDraftsRequest $request, ReturnWorkspace $query): JsonResponse
    {
        return response()->json($query->records((int) $request->validated('page', 1), ...array_values($request->filters())));
    }

    public function invoices(Request $request, ReturnWorkspace $query): JsonResponse
    {
        $input = $request->validate(['search' => ['nullable', 'string', 'max:150']]);

        return response()->json($query->invoices(trim($input['search'] ?? '')));
    }

    public function invoice(string $id, ReturnWorkspace $query): JsonResponse
    {
        $invoice = $query->invoice(strtolower($id));
        abort_if($invoice === null, 404);

        return response()->json($invoice);
    }

    public function store(Request $request, ManageReturnDraft $action): JsonResponse|RedirectResponse
    {
        $id = $action->save($request->only(['invoice_id', 'notes', 'lines']));

        return $request->expectsJson() ? response()->json(['id' => $id], 201) : back()->with('success', 'Draft retur berhasil disimpan.');
    }

    public function update(Request $request, string $id, ManageReturnDraft $action): JsonResponse|RedirectResponse
    {
        $action->save($request->only(['invoice_id', 'notes', 'lines', 'version']), strtolower($id));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Draft retur berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id, ManageReturnDraft $action): JsonResponse|RedirectResponse
    {
        $action->lifecycle(strtolower($id), $request->input('version'), false);

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Draft retur berhasil dihapus.');
    }

    public function restore(Request $request, string $id, ManageReturnDraft $action): JsonResponse|RedirectResponse
    {
        $action->lifecycle(strtolower($id), $request->input('version'), true);

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Draft retur berhasil dipulihkan.');
    }
}
