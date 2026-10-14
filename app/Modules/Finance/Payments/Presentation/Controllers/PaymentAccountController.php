<?php

namespace App\Modules\Finance\Payments\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Payments\Application\Actions\ManagePaymentAccount;
use App\Modules\Finance\Payments\Application\DTOs\PaymentAccountData;
use App\Modules\Finance\Payments\Application\Queries\ListPaymentMasters;
use App\Modules\Finance\Payments\Presentation\Requests\ListPaymentsRequest;
use App\Modules\Finance\Payments\Presentation\Requests\SavePaymentAccountRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PaymentAccountController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:finance.payments.view', only: ['index', 'data']), new Middleware('can:finance.payments.manage', only: ['store', 'update', 'destroy', 'restore'])];
    }

    public function index(ListPaymentsRequest $request, ListPaymentMasters $query): Response
    {
        $filters = $request->filters();

        return Inertia::render('finance/payments/accounts', ['records' => $query->accounts((int) $request->validated('page', 1), $filters), 'filters' => $filters]);
    }

    public function data(ListPaymentsRequest $request, ListPaymentMasters $query): JsonResponse
    {
        return response()->json($query->accounts((int) $request->validated('page', 1), $request->filters()));
    }

    public function store(SavePaymentAccountRequest $request, ManagePaymentAccount $action): JsonResponse|RedirectResponse
    {
        return $this->save($request, $action);
    }

    public function update(SavePaymentAccountRequest $request, string $id, ManagePaymentAccount $action): JsonResponse|RedirectResponse
    {
        return $this->save($request, $action, strtolower($id));
    }

    private function save(SavePaymentAccountRequest $request, ManagePaymentAccount $action, ?string $id = null): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        try {
            $saved = $action->save(new PaymentAccountData($data['code'], $data['name'], $data['type'], $data['bank_name'] ?? null, $data['account_number'] ?? null, $data['account_holder'] ?? null, $request->boolean('is_active', true)), $id);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'Kode sudah digunakan, termasuk pada data terhapus.']);
        }

        return $request->expectsJson() ? response()->json(['id' => $saved], $id === null ? 201 : 200) : back()->with('success', 'Akun kas/bank berhasil disimpan.');
    }

    public function destroy(Request $request, string $id, ManagePaymentAccount $action): JsonResponse|RedirectResponse
    {
        $action->delete(strtolower($id));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Akun berhasil dihapus.');
    }

    public function restore(Request $request, string $id, ManagePaymentAccount $action): JsonResponse|RedirectResponse
    {
        $action->restore(strtolower($id));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Akun berhasil dipulihkan.');
    }
}
