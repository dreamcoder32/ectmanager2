<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for central domains or if tenancy is not initialized
        if (!function_exists('tenant') || !tenant()) {
            return $next($request);
        }

        $tenant = tenant();

        if ($tenant->billing_expires_at && $tenant->billing_expires_at->isPast()) {
            // You might want to allow some routes like payment or contact support
            if ($request->routeIs('subscription.expired')) {
                return $next($request);
            }

            // Redirect to an expired page or abort
            // For now, let's return a simple response or redirect to a specific route
            return response()->markdown("# Subscription Expired\n\nPlease contact the administrator to renew your subscription.", 403);
            // Or redirect: return redirect()->route('subscription.expired');
        }

        return $next($request);
    }
}
