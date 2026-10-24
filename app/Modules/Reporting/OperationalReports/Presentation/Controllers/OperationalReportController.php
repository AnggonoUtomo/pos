<?php

namespace App\Modules\Reporting\OperationalReports\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reporting\OperationalReports\Application\Queries\OperationalReport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class OperationalReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:reporting.operational.view')];
    }

    public function index(Request $request, OperationalReport $query)
    {
        return Inertia::render('reporting/operational-reports/index', $query->handle($request->query()));
    }

    public function data(Request $request, OperationalReport $query)
    {
        return response()->json($query->handle($request->query()));
    }
}
