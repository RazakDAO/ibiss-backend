<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Les admins ont accès à toutes les routes sans restriction de rôle
        if ($request->user()?->role === 'admin') {
            return $next($request);
        }

        if (!$request->user() || $request->user()->role !== $role) {
            return response()->json([
                'message' => 'Accès non autorisé.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
