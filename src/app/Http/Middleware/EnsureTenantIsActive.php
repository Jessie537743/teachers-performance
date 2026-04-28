<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant && $tenant->status !== 'active') {
            return response()
                ->view('tenancy.suspended', ['tenant' => $tenant], 403);
        }

        return $next($request);
    }
}
