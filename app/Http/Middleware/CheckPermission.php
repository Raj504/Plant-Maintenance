<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side permission check.
 *
 * Route usage:  ->middleware('permission:pm.assets.view')
 * Several allowed (any one passes):  ->middleware('permission:pm.assets.view|pm.assets.edit')
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_active) {
            abort(403, 'Your account is inactive.');
        }

        $allowed = explode('|', $permissions);

        if (!$user->hasAnyPermission($allowed)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
