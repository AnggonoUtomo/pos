<?php

namespace App\Modules\Platform\Hardware\Presentation\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVirtualHardwareEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('pos_hardware.enabled') === true, 404);

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
