<?php

namespace App\Modules\Reporting\OperationalReports\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reporting\OperationalReports\Application\Queries\DashboardSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardSummary $query): Response
    {
        // Login permits the shell, not unrestricted business data.
        $viewAll = Gate::allows('sales.pos.invoices.view');

        return Inertia::render('dashboard', $query->handle(
            Gate::allows('reporting.operational.view'),
            $viewAll || Gate::allows('sales.pos.invoices.post'),
            $viewAll ? null : (int) $request->user()->id,
        ));
    }
}
