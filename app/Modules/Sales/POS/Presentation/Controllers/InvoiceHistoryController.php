<?php

namespace App\Modules\Sales\POS\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\POS\Application\Queries\ListInvoiceHistory;
use App\Modules\Sales\POS\Presentation\Requests\ListInvoiceHistoryRequest;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceHistoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(function (Request $request, Closure $next) {
            if (! Gate::allows('sales.pos.invoices.view')) {
                Gate::authorize('sales.pos.invoices.post');
            }

            return $next($request);
        })];
    }

    public function index(ListInvoiceHistoryRequest $request, ListInvoiceHistory $query): Response
    {
        return Inertia::render('sales/pos/history/index', $this->read($request, $query));
    }

    public function data(ListInvoiceHistoryRequest $request, ListInvoiceHistory $query): JsonResponse
    {
        return response()->json($this->read($request, $query));
    }

    private function read(ListInvoiceHistoryRequest $request, ListInvoiceHistory $query): array
    {
        return $query->handle((int) $request->validated('page', 1), $request->filters(), (int) $request->user()->id,
            Gate::allows('sales.pos.invoices.view'));
    }
}
