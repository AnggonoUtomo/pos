<?php

namespace App\Modules\Finance\Payments\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Payments\Application\Queries\ListPaymentHistory;
use App\Modules\Finance\Payments\Presentation\Requests\ListPaymentHistoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class PaymentHistoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:finance.payments.history.view')];
    }

    public function index(ListPaymentHistoryRequest $request, ListPaymentHistory $query): Response
    {
        $filters = $request->filters();

        return Inertia::render('finance/payments/history/index', ['records' => $query->records((int) $request->validated('page', 1), $filters), 'filters' => $filters, 'options' => $query->options()]);
    }

    public function data(ListPaymentHistoryRequest $request, ListPaymentHistory $query): JsonResponse
    {
        return response()->json($query->records((int) $request->validated('page', 1), $request->filters()));
    }

    public function show(string $direction, string $id, ListPaymentHistory $query): JsonResponse
    {
        return response()->json($query->detail($direction, $id));
    }

    public function refund(string $id, ListPaymentHistory $query): JsonResponse
    {
        return response()->json($query->detail('refund', $id));
    }
}
