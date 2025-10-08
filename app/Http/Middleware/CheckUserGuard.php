<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('client')->check()) {
            Auth::shouldUse('client');
        } elseif (Auth::guard('provider')->check()) {
            Auth::shouldUse('provider');
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
