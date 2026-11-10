<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && $user->hasAnyRole($roles), 403, 'You are not authorized to access this area.');

        $editorialOnlyRoute = $roles !== [] && array_diff($roles, ['editor', 'administrator']) === [];
        if ($editorialOnlyRoute) {
            abort_unless($user->isEditorialAllowlisted(), 403, 'Editorial access is restricted to accounts on the server-side allowlist.');
        }

        return $next($request);
    }
}
