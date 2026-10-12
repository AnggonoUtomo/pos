<?php

namespace App\Modules\Inventory\Stock\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Stock\Application\Actions\ManageOpening;
use App\Modules\Inventory\Stock\Application\Actions\PostOpening;
use App\Modules\Inventory\Stock\Application\Actions\PrepareOpening;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockOpeningController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:inventory.stock.manage', only: ['store', 'update', 'destroy', 'restore']),
            new Middleware('can:inventory.stock.post', only: ['post'])];
    }

    public function store(Request $request, ManageOpening $action): JsonResponse|RedirectResponse
    {
        $id = $this->execute(fn () => $action->create($request->validate(PrepareOpening::rules())));

        return $this->completed($request, $id, 'Draft stok awal tersimpan.', 201);
    }

    public function update(Request $request, string $id, ManageOpening $action): JsonResponse|RedirectResponse
    {
        $this->execute(fn () => $action->update($id, $request->validate(PrepareOpening::rules())));

        return $this->completed($request, $id, 'Draft stok awal diperbarui.');
    }

    public function destroy(Request $request, string $id, ManageOpening $action): JsonResponse|RedirectResponse
    {
        $this->execute(fn () => $action->delete($id));

        return $this->completed($request, $id, 'Draft stok awal dihapus.');
    }

    public function restore(Request $request, string $id, ManageOpening $action): JsonResponse|RedirectResponse
    {
        $this->execute(fn () => $action->restore($id));

        return $this->completed($request, $id, 'Draft stok awal dipulihkan.');
    }

    public function post(Request $request, string $id, PostOpening $action): JsonResponse|RedirectResponse
    {
        $this->execute(fn () => $action->handle($id, $request->user()->id));

        return $this->completed($request, $id, 'Stok awal berhasil diposting.');
    }

    private function execute(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['stock' => $exception->getMessage()]);
        }
    }

    private function completed(Request $request, string $id, string $message, int $status = 200): JsonResponse|RedirectResponse
    {
        return $request->expectsJson() ? response()->json(['id' => $id], $status) : back()->with('success', $message);
    }
}
