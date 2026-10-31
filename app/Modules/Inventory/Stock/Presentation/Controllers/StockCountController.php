<?php

namespace App\Modules\Inventory\Stock\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Stock\Application\Actions\ManageStockCount;
use App\Modules\Inventory\Stock\Application\Actions\PostStockCount;
use App\Modules\Inventory\Stock\Application\Contracts\StockCountRepository;
use App\Modules\Inventory\Stock\Application\Queries\StockWorkspace;
use Brick\Math\Exception\MathException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use InvalidArgumentException;

final class StockCountController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:inventory.stock.counts.view', only: ['index', 'show']),
            new Middleware('can:inventory.stock.counts.manage', only: ['store', 'save', 'add', 'submit', 'cancel']),
            new Middleware('can:inventory.stock.counts.post', only: ['post', 'reopen'])];
    }

    public function index(Request $request, StockCountRepository $counts, StockWorkspace $workspace)
    {
        $data = $request->validate(['page' => ['sometimes', 'integer', 'min:1'], 'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:counting,review,posted,cancelled'], 'warehouse_id' => ['nullable', 'ulid']]);
        $filters = ['search' => trim($data['search'] ?? ''), 'status' => $data['status'] ?? '', 'warehouse_id' => strtolower($data['warehouse_id'] ?? '')];

        return Inertia::render('inventory/stock/counts/index', ['records' => $counts->paginate($filters, (int) ($data['page'] ?? 1)),
            'filters' => $filters, 'warehouses' => $workspace->options()['warehouses'], 'operationKey' => strtolower((string) Str::ulid())]);
    }

    public function show(string $id, StockCountRepository $counts, StockWorkspace $workspace)
    {
        $record = $counts->find($id);
        unset($record['post_hash']);
        $options = $workspace->options();

        return Inertia::render('inventory/stock/counts/show', ['record' => $record, 'options' => $options]);
    }

    public function store(Request $request, ManageStockCount $action)
    {
        $data = $request->validate(['warehouse_id' => ['required', 'ulid'], 'notes' => ['required', 'string', 'max:500'], 'operation_key' => ['required', 'ulid']]);
        $id = $this->execute(fn () => $action->start($data['warehouse_id'], $data['notes'], $request->user()->id, $data['operation_key']));

        return $request->expectsJson() ? response()->json(['id' => $id], 201)
            : redirect()->route('inventory.stock.counts.show', $id)->with('success', 'Opname dimulai. Mutasi stok gudang dihentikan sementara.');
    }

    public function save(Request $request, string $id, ManageStockCount $action)
    {
        $version = $this->version($request);
        $lines = $request->validate(['lines' => ['required', 'array']])['lines'];
        $this->execute(fn () => $action->save($id, $version, $lines, $request->user()->id));

        return $this->done($request, 'Hasil hitung tersimpan.');
    }

    public function add(Request $request, string $id, ManageStockCount $action)
    {
        $version = $this->version($request);
        $itemId = $request->validate(['item_id' => ['required', 'ulid']])['item_id'];
        $this->execute(fn () => $action->add($id, $version, $itemId, $request->user()->id));

        return $this->done($request, 'Barang ditambahkan.');
    }

    public function submit(Request $request, string $id, ManageStockCount $action)
    {
        $version = $this->version($request);
        $this->execute(fn () => $action->submit($id, $version, $request->user()->id));

        return $this->done($request, 'Opname diajukan untuk review supervisor.');
    }

    public function cancel(Request $request, string $id, ManageStockCount $action)
    {
        return $this->reasonTransition($request, $id, $action, 'cancel');
    }

    public function reopen(Request $request, string $id, ManageStockCount $action)
    {
        return $this->reasonTransition($request, $id, $action, 'reopen');
    }

    private function reasonTransition(Request $request, string $id, ManageStockCount $action, string $method)
    {
        $version = $this->version($request);
        $reason = $request->validate(['reason' => ['required', 'string', 'max:500']])['reason'];
        $this->execute(fn () => $action->$method($id, $version, $reason, $request->user()->id));

        return $this->done($request, $method === 'cancel' ? 'Opname dibatalkan. Gudang dapat bertransaksi kembali.' : 'Opname dikembalikan untuk hitung ulang.');
    }

    public function post(Request $request, string $id, PostStockCount $action)
    {
        $version = $this->version($request);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500'], 'costs' => ['present', 'array']]);
        $this->execute(fn () => $action->handle($id, $version, $data['reason'], $data['costs'], $request->user()->id));

        return $this->done($request, 'Opname berhasil diposting. Gudang dapat bertransaksi kembali.');
    }

    private function version(Request $request): int
    {
        return (int) $request->validate(['version' => ['required', 'integer', 'min:0', 'max:2147483646']])['version'];
    }

    private function execute(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (InvalidArgumentException|MathException $error) {
            throw ValidationException::withMessages(['count' => $error instanceof MathException ? 'Kuantitas/nilai tidak dapat disimpan pada presisi enam desimal.' : $error->getMessage()]);
        }
    }

    private function done(Request $request, string $message)
    {
        return $request->expectsJson() ? response()->json(['message' => $message]) : back()->with('success', $message);
    }
}
