<?php

namespace App\Modules\Platform\OperationalSettings\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Platform\OperationalSettings\Application\Actions\RunManualBackup;
use App\Modules\Platform\OperationalSettings\Application\Actions\UpdateOperationalSettings;
use App\Modules\Platform\OperationalSettings\Application\Contracts\OperationalSettingsRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class OperationalSettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:platform.operational-settings.view', only: ['index']), new Middleware('can:platform.operational-settings.manage', only: ['update', 'backup'])];
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

    public function backup(Request $request, RunManualBackup $action)
    {
        if (array_diff(array_keys($request->all()), ['_token'])) {
            throw ValidationException::withMessages(['backup' => 'Field backup tidak diizinkan.']);
        }
        $file = $action->handle((int) $request->user()->id);

        return $request->expectsJson() ? response()->json(['status' => 'succeeded', 'file' => $file])
            : redirect()->route('platform.operational-settings.index')->with('success', 'Backup manual berhasil.');
    }
}
