<?php

namespace App\Modules\Sales\POS\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Payments\Application\Contracts\CashDrawerPosition;
use App\Modules\Sales\POS\Application\Actions\ManageCashierShift;
use App\Modules\Sales\POS\Application\Contracts\CashierShiftRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CashierShiftController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:sales.pos.invoices.post', only: ['current', 'drawer', 'open', 'close']),
            new Middleware('can:sales.pos.shifts.review', only: ['review'])];
    }

    private function authorizeRead(): void
    {
        abort_unless(Gate::any(['sales.pos.invoices.post', 'sales.pos.shifts.view']), 403);
    }

    private function payload(Request $request, array $rules): array
    {
        if (array_diff(array_keys($request->except('_token')), array_keys($rules))) {
            throw ValidationException::withMessages(['shift' => 'Payload shift mengandung field yang tidak diizinkan.']);
        }

        return $request->validate($rules);
    }

    public function index(): Response
    {
        $this->authorizeRead();

        return Inertia::render('sales/pos/shifts/index');
    }

    public function data(Request $request, CashierShiftRepository $shifts): JsonResponse
    {
        $this->authorizeRead();
        $data = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:1000000']]);

        return response()->json($shifts->paginate(Gate::allows('sales.pos.shifts.view') ? null : (int) $request->user()->id, (int) ($data['page'] ?? 1)));
    }

    public function current(Request $request, CashierShiftRepository $shifts, CashDrawerPosition $drawers): JsonResponse
    {
        return response()->json(['active' => $shifts->active((int) $request->user()->id), 'drawers' => $drawers->choices()]);
    }

    public function drawer(string $id, CashDrawerPosition $drawers): JsonResponse
    {
        return response()->json(['drawer' => $drawers->read(strtolower($id))->toArray()]);
    }

    public function show(string $id, Request $request, CashierShiftRepository $shifts, CashDrawerPosition $drawers): JsonResponse
    {
        $this->authorizeRead();
        $shift = $shifts->find(strtolower($id));
        abort_if($shift === null, 404);
        abort_unless($shift['actor_id'] === (int) $request->user()->id || Gate::allows('sales.pos.shifts.view'), 403);

        return response()->json(['shift' => $shift, 'position' => $shift['status'] === 'open' ? $drawers->read($shift['account_id'])->toArray() : null,
            'summary' => $shift['summary'] ?? $shifts->summary($shift['id'])]);
    }

    public function open(string $id, Request $request, ManageCashierShift $action): JsonResponse
    {
        $data = $this->payload($request, ['account_id' => ['required', 'ulid']]);

        return response()->json(['shift' => $action->open(strtolower($id), strtolower($data['account_id']), (int) $request->user()->id)]);
    }

    public function close(string $id, Request $request, ManageCashierShift $action): JsonResponse
    {
        $data = $this->payload($request, ['counted' => ['required', 'string'], 'reason' => ['nullable', 'string', 'max:200']]);

        return response()->json(['shift' => $action->close(strtolower($id), $data['counted'], $data['reason'] ?? '', (int) $request->user()->id)]);
    }

    public function review(string $id, Request $request, ManageCashierShift $action): JsonResponse
    {
        $data = $this->payload($request, ['note' => ['required', 'string', 'max:200']]);

        return response()->json(['shift' => $action->review(strtolower($id), $data['note'], (int) $request->user()->id)]);
    }
}
