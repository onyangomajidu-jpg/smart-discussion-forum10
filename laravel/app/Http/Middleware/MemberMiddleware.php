<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MemberMiddleware
{
    /**
     * Handle an incoming request.
     * Only allows authenticated users with 'member' role
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('error', 'Please login to access this page.');
        }

        if (!auth()->user()->isMember()) {
            abort(403, 'Access denied. Members only.');
        }

        if (auth()->user()->isBanned()) {
            abort(403, 'Your account has been suspended.');
        }

        return $next($request);
    }
}
