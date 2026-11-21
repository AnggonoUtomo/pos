<?php

namespace App\Modules\Platform\Hardware\Presentation\Controllers;

use App\Modules\Platform\Hardware\Application\Actions\CreateLabelJob;
use App\Modules\Platform\Hardware\Application\Actions\DispatchLabelJob;
use App\Modules\Platform\Hardware\Application\Actions\RecordLabelOutcome;
use App\Modules\Platform\Hardware\Application\Actions\RequestLabelReprint;
use App\Modules\Platform\Hardware\Presentation\Requests\HardwareRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LabelJobController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:platform.hardware.print'),
            new Middleware('can:inventory.view', only: ['store']),
            new Middleware('can:platform.hardware.reprint', only: ['reprint'])];
    }

    public function store(HardwareRequest $request, CreateLabelJob $action)
    {
        return response()->json(['job' => $action->handle($request->except('_token'), $request->user()->id)->toArray()], 201);
    }

    public function dispatch(HardwareRequest $request, DispatchLabelJob $action, string $job)
    {
        abort_if(count($request->except('_token')) > 0, 422, 'Dispatch tidak menerima payload perangkat.');

        return response()->json($action->handle($job, $request->user()->id));
    }

    public function outcome(HardwareRequest $request, RecordLabelOutcome $action, string $job)
    {
        return response()->json(['job' => $action->handle($job, $request->except('_token'), $request->user()->id)->toArray()]);
    }

    public function reprint(HardwareRequest $request, RequestLabelReprint $action, string $job)
    {
        return response()->json(['job' => $action->handle($job, $request->except('_token'), $request->user()->id,
            $request->user()->can('platform.hardware.manage'))->toArray()], 201);
    }
}
