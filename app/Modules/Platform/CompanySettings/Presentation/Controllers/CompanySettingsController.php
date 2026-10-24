<?php

namespace App\Modules\Platform\CompanySettings\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Platform\CompanySettings\Application\Actions\UpdateCompanySettings;
use App\Modules\Platform\CompanySettings\Application\Contracts\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class CompanySettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:platform.company-settings.view', only: ['index']), new Middleware('can:platform.company-settings.manage', only: ['update'])];
    }

    public function index(CompanyProfile $profile)
    {
        return Inertia::render('platform/company-settings/index', ['settings' => $profile->read()]);
    }

    public function update(Request $request, UpdateCompanySettings $action)
    {
        $action->handle($request->all(), $request->user()->id);

        return $request->expectsJson() ? response()->json(['status' => 'saved']) : back()->with('success', 'Pengaturan toko tersimpan.');
    }
}
