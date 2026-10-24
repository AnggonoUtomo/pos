<?php

namespace App\Modules\Sales\POS\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\POS\Application\DTOs\CartLineData;
use App\Modules\Sales\POS\Application\Queries\CheckCart;
use App\Modules\Sales\POS\Application\Queries\SaleCatalog;
use App\Modules\Sales\POS\Application\Queries\SaleContext;
use App\Modules\Sales\POS\Presentation\Requests\CartQuoteRequest;
use App\Modules\Sales\POS\Presentation\Requests\CatalogRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class POSController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:sales.pos.invoices.create', only: ['index', 'catalog', 'quote'])];
    }

    public function index(SaleContext $context): Response
    {
        return Inertia::render('sales/pos/index', $context->options());
    }

    public function catalog(CatalogRequest $request, SaleCatalog $query): JsonResponse
    {
        $data = $request->validated();

        return response()->json($query->handle($data['warehouse_id'], $data['customer_id'], $data['search'] ?? '', (int) ($data['page'] ?? 1)));
    }

    public function quote(CartQuoteRequest $request, CheckCart $query): JsonResponse
    {
        $data = $request->validated();
        $lines = array_map(fn ($line) => new CartLineData($line['item_id'], $line['unit_id'], $line['quantity']), $data['lines']);

        return response()->json($query->handle($data['warehouse_id'], $data['customer_id'], $lines, pricing: $data['pricing'] ?? []));
    }
}
