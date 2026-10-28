<?php

namespace App\Modules\Sales\POS\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\POS\Application\Actions\ManageHeldCart;
use App\Modules\Sales\POS\Application\Queries\HeldCarts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;

class HeldCartController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:sales.pos.invoices.create')];
    }

    public function index(Request $request, HeldCarts $query): JsonResponse
    {
        if (array_diff(array_keys($request->query()), ['page'])) {
            throw ValidationException::withMessages(['filter' => 'Filter tidak diizinkan.']);
        }
        $data = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:100000']]);

        return response()->json($query->listing((int) $request->user()->id, (int) ($data['page'] ?? 1)));
    }

    public function show(string $id, Request $request, HeldCarts $query): JsonResponse
    {
        return response()->json($query->recall($id, (int) $request->user()->id));
    }

    public function store(string $id, Request $request, ManageHeldCart $action): JsonResponse
    {
        return response()->json($action->save($id, $request->except('_token'), (int) $request->user()->id));
    }

    public function cancel(string $id, Request $request, ManageHeldCart $action): JsonResponse
    {
        if (array_diff(array_keys($request->except('_token')), ['version'])) {
            throw ValidationException::withMessages(['cart' => 'Input batal tidak diizinkan.']);
        }
        $data = $request->validate(['version' => ['required', 'integer', 'min:1', 'max:2147483646']]);
        $action->cancel($id, (int) $data['version'], (int) $request->user()->id);

        return response()->json(['cancelled' => true]);
    }
}
