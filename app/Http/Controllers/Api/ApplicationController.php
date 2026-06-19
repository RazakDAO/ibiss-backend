<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Offre;
use App\Notifications\ApplicationStatusNotification;
use App\Notifications\NewApplicationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApplicationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $candidat = $request->user()->candidate;

        $candidatures = Application::with('offre.company')
            ->where('candidate_id', $candidat->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return ApplicationResource::collection($candidatures);
    }

    public function store(StoreApplicationRequest $request, Offre $job): JsonResponse
    {
        if ($job->status !== 'active') {
            return response()->json([
                'message' => 'Cette offre n\'est plus disponible.',
                'data'    => null,
            ], 422);
        }

        $candidat = $request->user()->candidate;

        $dejaPostule = Application::where('offre_id', $job->id)
            ->where('candidate_id', $candidat->id)
            ->exists();

        if ($dejaPostule) {
            return response()->json([
                'message' => 'Vous avez déjà postulé à cette offre.',
                'data'    => null,
            ], 422);
        }

        $candidature = Application::create([
            'offre_id'     => $job->id,
            'candidate_id' => $candidat->id,
            'cover_letter' => $request->cover_letter,
            'status'       => 'new',
        ]);

        $candidature->load('offre.recruiter.user', 'candidate.user');

        // Notifier le recruteur
        if ($candidature->offre->recruiter?->user) {
            $candidature->offre->recruiter->user->notify(
                new NewApplicationNotification($candidature)
            );
        }

        return response()->json([
            'message' => 'Candidature envoyée avec succès.',
            'data'    => new ApplicationResource($candidature),
        ], 201);
    }

    public function parRecruteur(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $recruteur = $user->recruiter;

        $candidatures = Application::with(['candidate.user', 'offre'])
            ->when($user->role !== 'admin', fn ($q) => $q->whereHas('offre', fn ($q2) => $q2->where('recruiter_id', $recruteur->id)))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->offre_id, fn ($q, $v) => $q->where('offre_id', $v))
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApplicationResource::collection($candidatures);
    }

    public function parOffre(Request $request, Offre $job): JsonResponse|AnonymousResourceCollection
    {
        $recruteur = $request->user()->recruiter;

        if ($job->recruiter_id !== $recruteur?->id) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $candidatures = Application::with(['candidate.user'])
            ->where('offre_id', $job->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApplicationResource::collection($candidatures);
    }

    public function changerStatut(Request $request, Application $application): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:new,reviewing,interview,selected,reserve,rejected',
        ]);

        $user = $request->user();
        $recruteur = $user->recruiter;

        if ($user->role !== 'admin' && $application->offre->recruiter_id !== $recruteur?->id) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $application->update(['status' => $request->status]);
        $application->load('candidate.user', 'offre');

        // Notifier le candidat
        if ($application->candidate?->user) {
            $application->candidate->user->notify(
                new ApplicationStatusNotification($application, $request->status)
            );
        }

        return response()->json([
            'message' => 'Statut mis à jour.',
            'data'    => new ApplicationResource($application),
        ]);
    }

    public function ajouterNote(Request $request, Application $application): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();
        $recruteur = $user->recruiter;

        if ($user->role !== 'admin' && $application->offre->recruiter_id !== $recruteur?->id) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $application->update(['notes' => $request->notes]);

        return response()->json([
            'message' => 'Note enregistrée.',
            'data'    => new ApplicationResource($application),
        ]);
    }

    public function exporter(Request $request, Application $application): JsonResponse
    {
        $recruteur = $request->user()->recruiter;

        if ($application->offre->recruiter_id !== $recruteur?->id) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $application->load(['candidate.user', 'offre']);

        return response()->json([
            'message' => 'Données exportées.',
            'data'    => [
                'candidat'   => [
                    'nom'          => $application->candidate->user->name,
                    'email'        => $application->candidate->user->email,
                    'telephone'    => $application->candidate->user->phone,
                    'titre'        => $application->candidate->title,
                    'ville'        => $application->candidate->city,
                    'competences'  => $application->candidate->skills,
                    'cv_path'      => $application->candidate->cv_path,
                ],
                'candidature' => [
                    'statut'          => $application->status,
                    'lettre'          => $application->cover_letter,
                    'notes'           => $application->notes,
                    'date_candidature' => $application->created_at->toDateTimeString(),
                ],
                'offre' => [
                    'titre'  => $application->offre->title,
                    'type'   => $application->offre->type,
                    'ville'  => $application->offre->city,
                ],
            ],
        ]);
    }
}
