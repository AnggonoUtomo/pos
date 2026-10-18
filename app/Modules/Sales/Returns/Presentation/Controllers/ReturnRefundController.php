<?php

namespace App\Modules\Sales\Returns\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Returns\Application\Actions\SettleReturnRefund;
use App\Modules\Sales\Returns\Application\Queries\QuoteReturnRefund;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReturnRefundController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:sales.returns.refund')];
    }

    public function quote(string $id, QuoteReturnRefund $query): JsonResponse
    {
        return response()->json($query->handle($id));
    }

    public function store(Request $request, string $id, SettleReturnRefund $action): JsonResponse|RedirectResponse
    {
        $result = $action->handle($id, $request->except('_token'), $request->user()->id);

        return $request->expectsJson() ? response()->json($result) : back()->with('success', 'Refund retur berhasil diselesaikan.');
    }
}
