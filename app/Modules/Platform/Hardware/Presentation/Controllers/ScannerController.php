<?php

namespace App\Modules\Platform\Hardware\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Platform\Hardware\Application\Queries\ResolveScannedItem;
use App\Modules\Platform\Hardware\Presentation\Requests\ResolveScanRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ScannerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:inventory.view', only: ['index', 'resolve'])];
    }

    public function index(): Response
    {
        return Inertia::render('platform/hardware/scanner');
    }

    public function resolve(ResolveScanRequest $request, ResolveScannedItem $query): JsonResponse
    {
        $identity = $query->handle($request->validated('code'));
        if ($identity === null) {
            throw ValidationException::withMessages(['code' => 'Barcode atau SKU tidak ditemukan pada satuan jual aktif.']);
        }

        return response()->json($identity);
    }
}
