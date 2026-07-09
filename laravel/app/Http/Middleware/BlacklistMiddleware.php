<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlacklistMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->isBanned()) {
            $ban = auth()->user()->blacklists()
                ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->latest()->first();

            auth()->logout();

            return redirect()->route('login')->with(
                'error',
                'Your account is suspended until ' .
                ($ban?->expires_at?->format('M d, Y') ?? 'further notice') .
                '. Reason: ' . ($ban?->reason ?? 'policy violation')
            );
        }

        return $next($request);
    }
}
