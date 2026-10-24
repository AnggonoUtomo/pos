<?php

namespace App\Modules\Purchasing\PurchaseOrders\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Payments\Application\Contracts\PurchasePaymentMethods;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PayPurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ReceiveAndPayPurchase;
use App\Modules\Purchasing\PurchaseOrders\Application\Queries\PurchaseWorkspace;
use App\Modules\Purchasing\PurchaseOrders\Presentation\Requests\ListPurchaseOrdersRequest;
use App\Modules\Purchasing\PurchaseOrders\Presentation\Requests\PayPurchaseOrderRequest;
use App\Modules\Purchasing\PurchaseOrders\Presentation\Requests\PostPurchaseOrderRequest;
use App\Modules\Purchasing\PurchaseOrders\Presentation\Requests\SavePurchaseOrderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:purchasing.purchase-orders.view', only: ['index', 'data']),
            new Middleware('can:purchasing.purchase-orders.manage', only: ['store', 'update', 'destroy', 'restore']),
            new Middleware('can:purchasing.purchase-orders.post', only: ['post', 'receiveAndPay']),
            new Middleware('can:purchasing.purchase-orders.pay', only: ['pay', 'receiveAndPay'])];
    }

    public function index(ListPurchaseOrdersRequest $request, PurchaseWorkspace $query, PurchasePaymentMethods $methods): Response
    {
        $filters = $request->filters();

        return Inertia::render('purchasing/purchase-orders/index', ['records' => $query->records((int) $request->validated('page', 1), $filters), 'filters' => $filters,
            'options' => $query->options() + ['paymentMethods' => $request->user()->can('purchasing.purchase-orders.pay') ? $methods->choices() : []]]);
    }

    public function data(ListPurchaseOrdersRequest $request, PurchaseWorkspace $query): JsonResponse
    {
        return response()->json($query->records((int) $request->validated('page', 1), $request->filters()));
    }

    public function store(SavePurchaseOrderRequest $request, ManagePurchaseOrder $action): JsonResponse|RedirectResponse
    {
        $id = $action->save($request->validated());

        return $request->expectsJson() ? response()->json(['id' => $id], 201) : back()->with('success', 'Draft pembelian berhasil disimpan.');
    }

    public function update(SavePurchaseOrderRequest $request, string $id, ManagePurchaseOrder $action): JsonResponse|RedirectResponse
    {
        $action->save($request->validated(), strtolower($id));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Draft pembelian berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id, ManagePurchaseOrder $action): JsonResponse|RedirectResponse
    {
        $action->delete(strtolower($id), $request->input('version'));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Draft pembelian berhasil dihapus.');
    }

    public function restore(Request $request, string $id, ManagePurchaseOrder $action): JsonResponse|RedirectResponse
    {
        $action->restore(strtolower($id), $request->input('version'));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Draft pembelian berhasil dipulihkan.');
    }

    public function post(PostPurchaseOrderRequest $request, string $id, PostPurchaseOrder $action): JsonResponse|RedirectResponse
    {
        $result = $action->handle(strtolower($id), $request->validated('version'), $request->user()->id);

        return $request->expectsJson() ? response()->json($result) : back()->with('success', 'Barang pembelian berhasil diterima.');
    }

    public function pay(PayPurchaseOrderRequest $request, string $id, PayPurchaseOrder $action): JsonResponse|RedirectResponse
    {
        $result = $action->handle(strtolower($id), $request->validated('version'), $request->validated('payments'), $request->user()->id);

        return $request->expectsJson() ? response()->json($result) : back()->with('success', 'Pembayaran pembelian berhasil dicatat.');
    }

    public function receiveAndPay(PayPurchaseOrderRequest $request, string $id, ReceiveAndPayPurchase $action): JsonResponse|RedirectResponse
    {
        $result = $action->handle(strtolower($id), $request->validated('version'), $request->validated('payments'), $request->user()->id);

        return $request->expectsJson() ? response()->json($result) : back()->with('success', 'Pembelian diterima dan lunas.');
    }
}
