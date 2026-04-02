<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user || $user->blocked || $user->deleted_at || !in_array($user->role, $roles)) {
            abort(403, 'No autorizado.');
        }
        return $next($request);
    }
}
