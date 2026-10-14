<?php

namespace App\Modules\Finance\Payments\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Payments\Application\Actions\ManagePaymentMethod;
use App\Modules\Finance\Payments\Application\DTOs\PaymentMethodData;
use App\Modules\Finance\Payments\Application\Queries\ListPaymentMasters;
use App\Modules\Finance\Payments\Presentation\Requests\ListPaymentsRequest;
use App\Modules\Finance\Payments\Presentation\Requests\SavePaymentMethodRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:finance.payments.view', only: ['index', 'data']), new Middleware('can:finance.payments.manage', only: ['store', 'update', 'destroy', 'restore'])];
    }

    public function index(ListPaymentsRequest $request, ListPaymentMasters $query): Response
    {
        $filters = $request->filters();

        return Inertia::render('finance/payments/methods', ['records' => $query->methods((int) $request->validated('page', 1), $filters), 'filters' => $filters, 'accountOptions' => $query->accountChoices()]);
    }

    public function data(ListPaymentsRequest $request, ListPaymentMasters $query): JsonResponse
    {
        return response()->json($query->methods((int) $request->validated('page', 1), $request->filters()));
    }

    public function store(SavePaymentMethodRequest $request, ManagePaymentMethod $action): JsonResponse|RedirectResponse
    {
        return $this->save($request, $action);
    }

    public function update(SavePaymentMethodRequest $request, string $id, ManagePaymentMethod $action): JsonResponse|RedirectResponse
    {
        return $this->save($request, $action, strtolower($id));
    }

    private function save(SavePaymentMethodRequest $request, ManagePaymentMethod $action, ?string $id = null): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        try {
            $saved = $action->save(new PaymentMethodData($data['code'], $data['name'], $data['type'], $data['payment_account_id'], $request->boolean('is_active', true)), $id);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'Kode sudah digunakan, termasuk pada data terhapus.']);
        }

        return $request->expectsJson() ? response()->json(['id' => $saved], $id === null ? 201 : 200) : back()->with('success', 'Metode pembayaran berhasil disimpan.');
    }

    public function destroy(Request $request, string $id, ManagePaymentMethod $action): JsonResponse|RedirectResponse
    {
        $action->delete(strtolower($id));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Metode berhasil dihapus.');
    }

    public function restore(Request $request, string $id, ManagePaymentMethod $action): JsonResponse|RedirectResponse
    {
        $action->restore(strtolower($id));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Metode berhasil dipulihkan.');
    }
}
