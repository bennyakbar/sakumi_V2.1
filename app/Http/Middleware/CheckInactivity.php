<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckInactivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $timeout = (int) getSetting('inactivity_timeout', 7200);
            $lastActivity = session('last_activity', now()->timestamp);

            if (now()->timestamp - $lastActivity > $timeout) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('message', __('message.session_expired'));
            }

            session(['last_activity' => now()->timestamp]);
        }

        return $next($request);
    }
}
