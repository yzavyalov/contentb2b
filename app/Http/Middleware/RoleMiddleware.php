<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware extends Middleware
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {

        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        /*
         * Администратор имеет доступ везде.
         */
        if ($user->isAdmin()) {
            return $next($request);
        }

        $role = $user->role->value;

        if (!in_array($role, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
