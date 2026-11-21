<?php

namespace App\Modules\Platform\Hardware\Presentation\Controllers;

use App\Modules\Platform\Hardware\Application\Actions\ChangePrinterProfileStatus;
use App\Modules\Platform\Hardware\Application\Actions\SavePrinterProfile;
use App\Modules\Platform\Hardware\Application\Queries\HardwareWorkspace;
use App\Modules\Platform\Hardware\Presentation\Requests\HardwareRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class HardwareController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:platform.hardware.view', only: ['index', 'history']),
            new Middleware('can:platform.hardware.manage', only: ['store', 'update', 'status', 'destroy', 'restore']),
            new Middleware('can:platform.hardware.print', only: ['catalog']),
            new Middleware('can:inventory.view', only: ['catalog'])];
    }

    public function index(HardwareRequest $request, HardwareWorkspace $query)
    {
        return Inertia::render('platform/hardware/index', $query->page($request->user()->id,
            $request->user()->can('platform.hardware.manage'), $request->validated()));
    }

    public function catalog(HardwareRequest $request, HardwareWorkspace $query)
    {
        return response()->json($query->catalog((string) $request->input('q', ''), (int) $request->input('page', 1)));
    }

    public function history(HardwareRequest $request, HardwareWorkspace $query)
    {
        return response()->json($query->page($request->user()->id, $request->user()->can('platform.hardware.manage'), $request->validated())['history']);
    }

    public function store(HardwareRequest $request, SavePrinterProfile $action)
    {
        return response()->json(['profile' => $action->handle($request->except('_token'), $request->user()->id)->toArray()], 201);
    }

    public function update(HardwareRequest $request, SavePrinterProfile $action, string $printer)
    {
        return response()->json(['profile' => $action->handle($request->except('_token'), $request->user()->id, $printer)->toArray()]);
    }

    public function status(HardwareRequest $request, ChangePrinterProfileStatus $action, string $printer)
    {
        return response()->json(['profile' => $action->handle($printer, 'status', $request->except('_token'), $request->user()->id)->toArray()]);
    }

    public function destroy(HardwareRequest $request, ChangePrinterProfileStatus $action, string $printer)
    {
        return response()->json(['profile' => $action->handle($printer, 'delete', $request->except('_token'), $request->user()->id)->toArray()]);
    }

    public function restore(HardwareRequest $request, ChangePrinterProfileStatus $action, string $printer)
    {
        return response()->json(['profile' => $action->handle($printer, 'restore', $request->except('_token'), $request->user()->id)->toArray()]);
    }
}
