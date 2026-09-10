<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsPro
{
    /**
     * Gate a route behind an active Pro license.
     *
     * A free user landing here is a sales opportunity rather than an error, so
     * they are sent to billing instead of being shown a 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->is_pro) {
            return $next($request);
        }

        return redirect()
            ->route('billing')
            ->with('success', 'That feature is part of Song Rank Pro.');
    }
}
