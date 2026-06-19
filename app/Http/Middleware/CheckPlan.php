<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlan
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    protected const HIERARCHY = ['free', 'starter', 'pro', 'rh', 'enterprise'];

    public function handle(Request $request, Closure $next, string $planMinimum): Response
    {
        // Les admins ont accès à tous les plans sans restriction
        if ($request->user()?->role === 'admin') {
            return $next($request);
        }

        $recruteur = $request->user()?->recruiter;

        if (!$recruteur) {
            return response()->json(['message' => 'Profil recruteur introuvable.', 'data' => null], 403);
        }

        $indexActuel = array_search($recruteur->plan, self::HIERARCHY);
        $indexRequis = array_search($planMinimum, self::HIERARCHY);

        if ($indexActuel === false || $indexActuel < $indexRequis) {
            return response()->json([
                'message' => "Cette fonctionnalité nécessite le plan {$planMinimum} ou supérieur.",
                'data' => null,
            ], 403);
        }

        if ($recruteur->plan !== 'free' && !$recruteur->aPlanActif()) {
            return response()->json([
                'message' => 'Votre abonnement a expiré. Veuillez renouveler votre plan.',
                'data'    => null,
            ], 403);
        }

        return $next($request);
    }
}
