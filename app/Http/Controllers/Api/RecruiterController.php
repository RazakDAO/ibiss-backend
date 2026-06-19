<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecruiterResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecruiterController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $recruteur = $request->user()->recruiter;

        if (! $recruteur) {
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'message' => 'Profil recruteur introuvable. Veuillez créer votre profil.',
                    'data'    => null,
                ], 404);
            }

            // Créer automatiquement un profil recruteur enterprise pour l'admin
            $recruteur = \App\Models\Recruiter::create([
                'user_id' => $request->user()->id,
                'plan'    => 'enterprise',
            ]);
        }

        $recruteur->load(['user', 'company']);

        return response()->json([
            'message' => 'Profil recruteur.',
            'data'    => new RecruiterResource($recruteur),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20|unique:users,phone,' . $request->user()->id,
        ], [
            'phone.unique' => 'Ce numéro est déjà utilisé.',
        ]);

        $request->user()->fill($request->only('name', 'phone'))->save();

        return response()->json([
            'message' => 'Profil mis à jour.',
            'data'    => new RecruiterResource($request->user()->recruiter->refresh()->load(['user', 'company'])),
        ]);
    }

    public function attacherEntreprise(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $recruteur = $request->user()->recruiter;

        if ($recruteur->company_id) {
            return response()->json([
                'message' => 'Vous êtes déjà associé à une entreprise.',
                'data'    => null,
            ], 422);
        }

        $company = Company::findOrFail($request->company_id);
        $recruteur->update(['company_id' => $company->id]);

        return response()->json([
            'message'       => 'Entreprise associée avec succès.',
            'data'          => new RecruiterResource($recruteur->refresh()->load(['user', 'company'])),
            'avertissement' => !$company->is_verified
                ? 'Cette entreprise n\'est pas encore vérifiée. Vos offres passeront en modération.'
                : null,
        ]);
    }
}
