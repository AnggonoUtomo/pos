<?php

namespace App\Modules\Inventory\Stock\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Stock\Application\Actions\ManageMinimumStock;
use App\Modules\Inventory\Stock\Application\Contracts\MinimumStockRepository;
use App\Modules\Inventory\Stock\Application\Queries\StockWorkspace;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use InvalidArgumentException;

final class MinimumStockController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:inventory.stock.minimums.view', only: ['index']),
            new Middleware('can:inventory.stock.minimums.manage', only: ['store', 'update', 'destroy', 'restore'])];
    }

    public function index(Request $request, MinimumStockRepository $records, StockWorkspace $workspace)
    {
        $data = $request->validate(['page' => ['sometimes', 'integer', 'min:1'], 'search' => ['nullable', 'string', 'max:150'],
            'warehouse_id' => ['nullable', 'ulid'], 'status' => ['nullable', 'in:all,low,healthy,disabled,unavailable'],
            'trashed' => ['sometimes', 'boolean']]);
        $filters = ['search' => trim($data['search'] ?? ''), 'warehouse_id' => strtolower($data['warehouse_id'] ?? ''),
            'status' => $data['status'] ?? 'all', 'trashed' => $request->boolean('trashed')];

        return Inertia::render('inventory/stock/minimums/index', ['records' => $records->paginate($filters, (int) ($data['page'] ?? 1)),
            'filters' => $filters, 'options' => $workspace->options()]);
    }

    public function store(Request $request, ManageMinimumStock $action)
    {
        $id = $this->execute(fn () => $action->create($request->all(), (int) $request->user()->id));

        return $request->expectsJson() ? response()->json(['id' => $id], 201)
            : back()->with('success', 'Stok minimum ditambahkan.');
    }

    public function update(Request $request, string $id, ManageMinimumStock $action)
    {
        $this->execute(fn () => $action->update($id, $request->all(), (int) $request->user()->id));

        return $this->done($request, 'Stok minimum tersimpan.');
    }

    public function destroy(Request $request, string $id, ManageMinimumStock $action)
    {
        return $this->remove($request, $id, $action, false);
    }

    public function restore(Request $request, string $id, ManageMinimumStock $action)
    {
        return $this->remove($request, $id, $action, true);
    }

    private function remove(Request $request, string $id, ManageMinimumStock $action, bool $restore)
    {
        $version = (int) $request->validate(['version' => ['required', 'integer', 'min:0', 'max:2147483646']])['version'];
        $this->execute(fn () => $action->remove($id, $version, $restore, (int) $request->user()->id));

        return $this->done($request, $restore ? 'Stok minimum dipulihkan.' : 'Stok minimum dihapus.');
    }

    private function execute(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (InvalidArgumentException $error) {
            throw ValidationException::withMessages(['minimum' => $error->getMessage()]);
        }
    }

    private function done(Request $request, string $message)
    {
        return $request->expectsJson() ? response()->json(['message' => $message]) : back()->with('success', $message);
    }
}
