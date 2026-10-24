<?php

namespace App\Modules\Inventory\Stock\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Stock\Application\Actions\PostAdjustment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AdjustmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:inventory.stock.adjust')];
    }

    public function store(Request $request, PostAdjustment $action)
    {
        $id = $action->handle($request->all(), $request->user()->id);

        return $request->expectsJson() ? response()->json(['id' => $id], 201) : back()->with('success', 'Koreksi stok berhasil diposting.');
    }
}
