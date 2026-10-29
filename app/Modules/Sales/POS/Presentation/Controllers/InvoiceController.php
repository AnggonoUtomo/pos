<?php

namespace App\Modules\Sales\POS\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Payments\Application\Contracts\SalePaymentMethods;
use App\Modules\Sales\POS\Application\Actions\PostInvoice;
use App\Modules\Sales\POS\Application\Contracts\CashierShiftRepository;
use App\Modules\Sales\POS\Application\Queries\GetInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:sales.pos.invoices.create', only: ['store', 'methods']),
            new Middleware('can:sales.pos.invoices.post', only: ['store', 'methods'])];
    }

    public function store(Request $request, PostInvoice $action): JsonResponse
    {
        return response()->json($action->handle($request->except('_token'), (int) $request->user()->id), 201);
    }

    public function show(string $id, Request $request, GetInvoice $query): JsonResponse
    {
        $viewAll = Gate::allows('sales.pos.invoices.view');
        if (! $viewAll) {
            Gate::authorize('sales.pos.invoices.post');
        }

        return response()->json($query->handle($id, (int) $request->user()->id, $viewAll));
    }

    public function methods(Request $request, SalePaymentMethods $methods, CashierShiftRepository $shifts): JsonResponse
    {
        $active = $shifts->active((int) $request->user()->id);

        return response()->json(['methods' => $active === null ? [] : $methods->forDrawer($active['account_id'])]);
    }
}
