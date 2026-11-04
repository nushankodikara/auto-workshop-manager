<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotArchived
{
    /**
     * Handle an incoming request.
     * Prevents archived users with active sessions or cookies from accessing the system.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && (bool) Auth::user()->is_archived) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated/archived. Please contact management.',
            ]);
        }

        return $next($request);
    }
}

