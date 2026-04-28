<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level capability gate. Usage:
 *
 *   Route::get('/predictions', ...)->middleware('plan.feature:ai_predictions');
 *
 * If the current tenant's plan lacks the capability, the user is redirected
 * to the upgrade page (or 403 for JSON requests).
 */
class EnsurePlanFeature
{
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        if (plan()->has($capability)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, "Your plan does not include: {$capability}");
        }

        return redirect()->route('plan.upgrade', ['feature' => $capability])
            ->with('plan_required', $capability);
    }
}
