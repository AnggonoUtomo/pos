<?php

namespace App\Modules\Platform\OperationalSettings\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Platform\OperationalSettings\Application\Actions\UpdateOperationalSettings;
use App\Modules\Platform\OperationalSettings\Application\Contracts\OperationalSettingsRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class OperationalSettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:platform.operational-settings.view', only: ['index']), new Middleware('can:platform.operational-settings.manage', only: ['update'])];
    }

    public function index(OperationalSettingsRepository $settings)
    {
        return Inertia::render('platform/operational-settings/index', ['settings' => $settings->read(), 'backup' => $settings->status()]);
    }

    public function update(Request $request, UpdateOperationalSettings $action)
    {
        $action->handle($request->all(), $request->user()->id);

        return $request->expectsJson() ? response()->json(['status' => 'saved']) : back()->with('success', 'Pengaturan operasional tersimpan.');
    }
}
