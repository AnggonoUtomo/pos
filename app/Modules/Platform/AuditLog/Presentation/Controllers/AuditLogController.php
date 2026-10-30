<?php

namespace App\Modules\Platform\AuditLog\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Platform\AuditLog\Application\Queries\ListAuditLog;
use App\Modules\Platform\AuditLog\Presentation\Requests\ListAuditLogRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:platform.audit-log.view')];
    }

    public function index(ListAuditLogRequest $request, ListAuditLog $query): Response
    {
        return Inertia::render('platform/audit-log/index', ['records' => $query->records((int) $request->validated('page', 1), $request->filters()),
            'filters' => $request->filters(), 'options' => $query->options()]);
    }

    public function data(ListAuditLogRequest $request, ListAuditLog $query): JsonResponse
    {
        return response()->json($query->records((int) $request->validated('page', 1), $request->filters()));
    }

    public function show(string $id, ListAuditLog $query): JsonResponse
    {
        return response()->json($query->detail($id));
    }
}
