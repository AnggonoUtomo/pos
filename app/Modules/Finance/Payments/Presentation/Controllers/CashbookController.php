<?php

namespace App\Modules\Finance\Payments\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Payments\Application\Actions\ManageCashbook;
use App\Modules\Finance\Payments\Application\Contracts\CashbookReader;
use App\Modules\Finance\Payments\Presentation\Requests\CashbookRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class CashbookController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:finance.cashbook.view', only: ['index', 'data']), new Middleware('can:finance.cashbook.open', only: ['open']),
            new Middleware('can:finance.cashbook.manage', only: ['store']), new Middleware('can:finance.cashbook.reverse', only: ['reverse'])];
    }

    public function index(CashbookRequest $request, CashbookReader $reader): Response
    {
        return Inertia::render('finance/payments/cashbook/index', ['records' => $this->records($request, $reader), 'accounts' => $reader->accounts(), 'filters' => $request->validated(), 'today' => now('Asia/Jakarta')->toDateString()]);
    }

    private function records(CashbookRequest $request, CashbookReader $reader): array
    {
        return $reader->statement($request->validated('account_id'), $request->validated('date_from'), $request->validated('date_to'), (int) $request->validated('page', 1));
    }

    public function data(CashbookRequest $request, CashbookReader $reader): JsonResponse
    {
        return response()->json($this->records($request, $reader));
    }

    public function open(CashbookRequest $request, ManageCashbook $action): JsonResponse
    {
        return response()->json(['id' => $action->open($request->validated(), (int) $request->user()->id)]);
    }

    public function store(CashbookRequest $request, ManageCashbook $action): JsonResponse
    {
        try {
            return response()->json(['id' => $action->post($request->validated(), (int) $request->user()->id)]);
        } catch (UniqueConstraintViolationException) {
            abort(409, 'Kunci pengiriman sudah digunakan. Periksa mutasi sebelum membuat yang baru.');
        }
    }

    public function reverse(CashbookRequest $request, string $id, ManageCashbook $action): JsonResponse
    {
        return response()->json(['id' => $action->reverse(strtolower($id), $request->validated('reason'), (int) $request->user()->id)]);
    }
}
