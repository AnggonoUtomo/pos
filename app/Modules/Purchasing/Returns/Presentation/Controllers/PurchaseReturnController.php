<?php

namespace App\Modules\Purchasing\Returns\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchasing\Returns\Application\Actions\ManagePurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Application\Actions\PostPurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Application\Actions\SettleReturnRefund;
use App\Modules\Purchasing\Returns\Application\Queries\QuoteReturnRefund;
use App\Modules\Purchasing\Returns\Application\Queries\ReturnWorkspace;
use App\Modules\Purchasing\Returns\Presentation\Requests\LifecyclePurchaseReturnRequest;
use App\Modules\Purchasing\Returns\Presentation\Requests\ListPurchaseReturnDraftsRequest;
use App\Modules\Purchasing\Returns\Presentation\Requests\SavePurchaseReturnDraftRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final class PurchaseReturnController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:purchasing.returns.view', only: ['index', 'data']),
            new Middleware('can:purchasing.returns.manage', only: ['purchases', 'purchase', 'store', 'update', 'destroy', 'restore']),
            new Middleware('can:purchasing.returns.post', only: ['post']),
            new Middleware('can:purchasing.returns.refund', only: ['refundQuote', 'refund'])];
    }

    public function refundQuote(string $id, QuoteReturnRefund $query): JsonResponse
    {
        return response()->json($query->handle(strtolower($id)));
    }

    public function refund(Request $request, string $id, SettleReturnRefund $action): JsonResponse|RedirectResponse
    {
        $result = $action->handle(strtolower($id), $request->all(), $request->user()->id);

        return $request->expectsJson() ? response()->json($result) : back()->with('success', 'Penerimaan uang retur supplier berhasil dicatat.');
    }

    public function index(ListPurchaseReturnDraftsRequest $request, ReturnWorkspace $query): Response
    {
        $filters = $request->filters();

        return Inertia::render('purchasing/returns/index', [
            'records' => $query->records((int) $request->validated('page', 1), $filters),
            'filters' => $filters,
        ]);
    }

    public function data(ListPurchaseReturnDraftsRequest $request, ReturnWorkspace $query): JsonResponse
    {
        return response()->json($query->records((int) $request->validated('page', 1), $request->filters()));
    }

    public function purchases(Request $request, ReturnWorkspace $query): JsonResponse
    {
        $input = $request->validate(['search' => ['nullable', 'string', 'max:150']]);

        return response()->json(['data' => $query->purchases(trim($input['search'] ?? ''))]);
    }

    public function purchase(string $id, ReturnWorkspace $query): JsonResponse
    {
        $purchase = $query->purchase(strtolower($id));
        abort_if($purchase === null, 404);

        return response()->json($purchase);
    }

    public function store(SavePurchaseReturnDraftRequest $request, ManagePurchaseReturnDraft $action): JsonResponse|RedirectResponse
    {
        $id = $action->save($request->validated());

        return $request->expectsJson() ? response()->json(['id' => $id], 201) : back()->with('success', 'Draft retur pembelian berhasil disimpan.');
    }

    public function update(SavePurchaseReturnDraftRequest $request, string $id, ManagePurchaseReturnDraft $action): JsonResponse|RedirectResponse
    {
        $action->save($request->validated(), strtolower($id));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Draft retur pembelian berhasil diperbarui.');
    }

    public function destroy(LifecyclePurchaseReturnRequest $request, string $id, ManagePurchaseReturnDraft $action): JsonResponse|RedirectResponse
    {
        $action->delete(strtolower($id), $request->validated('version'));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Draft retur pembelian berhasil dihapus.');
    }

    public function restore(LifecyclePurchaseReturnRequest $request, string $id, ManagePurchaseReturnDraft $action): JsonResponse|RedirectResponse
    {
        $action->restore(strtolower($id), $request->validated('version'));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Draft retur pembelian berhasil dipulihkan.');
    }

    public function post(LifecyclePurchaseReturnRequest $request, string $id, PostPurchaseReturnDraft $action): JsonResponse|RedirectResponse
    {
        $result = $action->handle(strtolower($id), $request->validated('version'), $request->user()->id);

        return $request->expectsJson() ? response()->json($result) : back()->with('success', 'Barang retur pembelian berhasil diserahkan.');
    }
}
