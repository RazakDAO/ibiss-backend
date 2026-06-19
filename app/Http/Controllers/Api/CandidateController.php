<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCandidateRequest;
use App\Http\Resources\CandidateResource;
use App\Models\Candidate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CandidateController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $candidat = $request->user()->candidate->load('user');

        return response()->json([
            'message' => 'Profil récupéré.',
            'data'    => new CandidateResource($candidat),
        ]);
    }

    public function update(UpdateCandidateRequest $request): JsonResponse
    {
        $user     = $request->user();
        $candidat = $user->candidate;

        // Mise à jour du nom et téléphone sur le user
        if ($request->has('name') || $request->has('phone')) {
            $user->fill($request->only('name', 'phone'))->save();
        }

        // Mise à jour du profil candidat
        $candidat->fill($request->only(
            'title', 'bio', 'city', 'visibility',
            'skills', 'experiences', 'education', 'alert_preferences'
        ))->save();

        return response()->json([
            'message' => 'Profil mis à jour.',
            'data'    => new CandidateResource($candidat->refresh()->load('user')),
        ]);
    }

    public function uploadCv(Request $request): JsonResponse
    {
        $request->validate([
            'cv' => [
                'required',
                'file',
                'mimes:pdf',
                'max:5120', // 5 Mo max
            ],
        ], [
            'cv.required' => 'Veuillez sélectionner un fichier CV.',
            'cv.mimes'    => 'Le CV doit être au format PDF.',
            'cv.max'      => 'Le CV ne doit pas dépasser 5 Mo.',
        ]);

        $candidat = $request->user()->candidate;

        // Supprimer l'ancien CV s'il existe
        if ($candidat->cv_path) {
            Storage::disk('local')->delete($candidat->cv_path);
        }

        // Stocker le nouveau CV
        $chemin = $request->file('cv')->storeAs(
            'cvs',
            'cv_' . $candidat->id . '_' . time() . '.pdf',
            'local'
        );

        $candidat->update(['cv_path' => $chemin]);

        return response()->json([
            'message' => 'CV uploadé avec succès.',
            'data'    => [
                'cv_url' => Storage::url($chemin),
            ],
        ]);
    }

    // Liste CVthèque pour les recruteurs (plan starter+)
    public function index(Request $request): JsonResponse
    {
        $candidats = Candidate::with('user')
            ->where('visibility', '!=', 'private')
            ->when($request->q, function ($query, $q) {
                $query->where(function ($sq) use ($q) {
                    $sq->where('title', 'like', "%{$q}%")
                       ->orWhere('city', 'like', "%{$q}%")
                       ->orWhereJsonContains('skills', $q);
                });
            })
            ->when($request->city, fn ($q, $v) => $q->where('city', $v))
            ->orderByDesc('updated_at')
            ->paginate(20);

        return response()->json([
            'message' => 'Liste des candidats.',
            'data'    => CandidateResource::collection($candidats),
            'meta'    => [
                'total'        => $candidats->total(),
                'current_page' => $candidats->currentPage(),
                'last_page'    => $candidats->lastPage(),
            ],
        ]);
    }

    public function profil(Request $request, Candidate $candidate): JsonResponse
    {
        if ($candidate->visibility === 'private') {
            return response()->json(['message' => 'Profil non accessible.', 'data' => null], 403);
        }

        $candidate->load('user');

        return response()->json([
            'message' => 'Profil candidat.',
            'data'    => new CandidateResource($candidate),
        ]);
    }
}
