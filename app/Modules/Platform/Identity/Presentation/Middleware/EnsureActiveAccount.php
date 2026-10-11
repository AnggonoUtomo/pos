<?php

namespace App\Modules\Platform\Identity\Presentation\Middleware;

use App\Modules\Platform\Identity\Application\Contracts\AccessRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveAccount
{
    public function __construct(private AccessRepository $access) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user) {
            $state = $this->access->sessionState((int) $user->id);
            if ($state && $state['active'] && Auth::guard('web')->viaRemember()) {
                $request->session()->put('identity.access_version', $state['version']);
            }
            if (! $state || ! $state['active'] || (int) $request->session()->get('identity.access_version', 0) !== $state['version']) {
                Auth::guard('web')->logoutCurrentDevice();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return $request->expectsJson() ? response()->json(['message' => 'Sesi akun tidak lagi aktif.'], 401)
                    : redirect()->route('login')->with('status', 'Sesi akun berakhir. Silakan login kembali.');
            }
        }

        return $next($request);
    }
}
