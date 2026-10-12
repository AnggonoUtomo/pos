<?php

namespace App\Modules\Sales\Parties\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Parties\Application\Actions\ManageCustomerLevel;
use App\Modules\Sales\Parties\Application\DTOs\CustomerLevelData;
use App\Modules\Sales\Parties\Application\Queries\ListParties;
use App\Modules\Sales\Parties\Presentation\Requests\ListPartiesRequest;
use App\Modules\Sales\Parties\Presentation\Requests\SaveCustomerLevelRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class CustomerLevelController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:sales.parties.view', only: ['index', 'data']),
            new Middleware('can:sales.parties.manage', only: ['store', 'update', 'destroy', 'restore'])];
    }

    public function index(ListPartiesRequest $request, ListParties $query): Response
    {
        $filters = $request->filters();

        return Inertia::render('sales/parties/customer-levels', ['records' => $query->levels((int) $request->validated('page', 1), $filters), 'filters' => $filters]);
    }

    public function data(ListPartiesRequest $request, ListParties $query): JsonResponse
    {
        return response()->json($query->levels((int) $request->validated('page', 1), $request->filters()));
    }

    public function store(SaveCustomerLevelRequest $request, ManageCustomerLevel $action): JsonResponse|RedirectResponse
    {
        return $this->save($request, $action);
    }

    public function update(SaveCustomerLevelRequest $request, string $id, ManageCustomerLevel $action): JsonResponse|RedirectResponse
    {
        return $this->save($request, $action, strtolower($id));
    }

    private function save(SaveCustomerLevelRequest $request, ManageCustomerLevel $action, ?string $id = null): JsonResponse|RedirectResponse
    {
        $input = $request->validated();
        try {
            $saved = $action->save(new CustomerLevelData($input['code'], $input['name'], $request->boolean('is_active', true)), $id);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'Kode sudah digunakan, termasuk pada data terhapus.']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['parties' => $exception->getMessage()]);
        }

        return $request->expectsJson() ? response()->json(['id' => $saved], $id === null ? 201 : 200) : back()->with('success', 'Level pelanggan berhasil disimpan.');
    }

    public function destroy(Request $request, string $id, ManageCustomerLevel $action): JsonResponse|RedirectResponse
    {
        try {
            $action->delete(strtolower($id));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['parties' => $exception->getMessage()]);
        }

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Level pelanggan berhasil dihapus.');
    }

    public function restore(Request $request, string $id, ManageCustomerLevel $action): JsonResponse|RedirectResponse
    {
        $action->restore(strtolower($id));

        return $request->expectsJson() ? response()->json(['id' => strtolower($id)]) : back()->with('success', 'Level pelanggan berhasil dipulihkan.');
    }
}
