<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stops a second purchase before it reaches the controller.
 *
 * Keeping this at the route level is what lets ProCheckoutController::store()
 * return a Checkout and nothing else, rather than branching on entitlement.
 */
class RedirectIfAlreadyPro
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->is_pro) {
            return redirect()
                ->route('billing')
                ->with('success', 'You already have Song Rank Pro.');
        }

        return $next($request);
    }
}
