<?php

namespace App\Modules\Purchasing\Returns\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchasing\Returns\Application\Actions\ManagePurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Application\Queries\ReturnWorkspace;
use App\Modules\Purchasing\Returns\Presentation\Requests\ListPurchaseReturnDraftsRequest;
use App\Modules\Purchasing\Returns\Presentation\Requests\SavePurchaseReturnDraftRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final class PurchaseReturnController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:purchasing.returns.view', only: ['data']),
            new Middleware('can:purchasing.returns.manage', only: ['purchases', 'purchase', 'store', 'update'])];
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
}
