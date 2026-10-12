<?php

namespace App\Modules\Sales\Parties\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Parties\Application\Actions\ManageCustomer;
use App\Modules\Sales\Parties\Application\DTOs\CustomerData;
use App\Modules\Sales\Parties\Application\Queries\ListParties;
use App\Modules\Sales\Parties\Presentation\Requests\ListPartiesRequest;
use App\Modules\Sales\Parties\Presentation\Requests\SaveCustomerRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:sales.parties.view', only: ['index', 'data']),
            new Middleware('can:sales.parties.manage', only: ['store', 'update', 'destroy', 'restore'])];
    }

    public function index(ListPartiesRequest $request, ListParties $query): Response
    {
        $filters = $request->filters();

        return Inertia::render('sales/parties/index', ['records' => $query->customers((int) $request->validated('page', 1), $filters),
            'filters' => $filters, 'levelOptions' => $query->levelChoices()]);
    }

    public function data(ListPartiesRequest $request, ListParties $query): JsonResponse
    {
        return response()->json($query->customers((int) $request->validated('page', 1), $request->filters()));
    }

    public function store(SaveCustomerRequest $request, ManageCustomer $action): JsonResponse|RedirectResponse
    {
        return $this->save($request, $action);
    }

    public function update(SaveCustomerRequest $request, string $id, ManageCustomer $action): JsonResponse|RedirectResponse
    {
        return $this->save($request, $action, strtolower($id));
    }

    private function save(SaveCustomerRequest $request, ManageCustomer $action, ?string $id = null): JsonResponse|RedirectResponse
    {
        $input = $request->validated();
        try {
            $saved = $action->save(new CustomerData($input['code'], $input['name'], $input['customer_level_id'],
                $input['phone'] ?? null, $input['email'] ?? null, $input['address'] ?? null, $request->boolean('is_active', true)), $id);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'Kode sudah digunakan, termasuk pada data terhapus.']);
        }

        return $request->expectsJson() ? response()->json(['id' => $saved], $id === null ? 201 : 200) : back()->with('success', 'Pelanggan berhasil disimpan.');
    }

    public function destroy(Request $request, string $id, ManageCustomer $action): JsonResponse|RedirectResponse
    {
        $action->delete(strtolower($id));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Pelanggan berhasil dihapus.');
    }

    public function restore(Request $request, string $id, ManageCustomer $action): JsonResponse|RedirectResponse
    {
        $action->restore(strtolower($id));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Pelanggan berhasil dipulihkan.');
    }
}
