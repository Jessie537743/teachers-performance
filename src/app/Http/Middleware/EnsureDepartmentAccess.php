<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDepartmentAccess
{
    /**
     * For dean/head roles, ensures the requested resource belongs to their department.
     * Expects 'facultyId' or 'faculty' route parameter that maps to a faculty profile.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Only restrict dean/head roles
        if (!$user->hasRole(['dean', 'head'])) {
            return $next($request);
        }

        // Check if there's a facultyId in the route
        $facultyId = $request->route('facultyId') ?? $request->route('faculty');

        if ($facultyId) {
            $faculty = \App\Models\FacultyProfile::find($facultyId);
            if ($faculty && $faculty->department_id !== $user->department_id) {
                abort(403, 'You can only access faculty in your department.');
            }
        }

        return $next($request);
    }
}
