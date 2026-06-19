<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Http\Resources\JobResource;
use App\Models\Offre;
use App\Services\JobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class JobController extends Controller
{
    public function __construct(private JobService $jobService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $offres = $this->jobService->lister($request->only([
            'q', 'sector', 'city', 'type', 'level',
        ]));

        return JobResource::collection($offres);
    }

    public function show(Offre $job): JsonResource|JsonResponse
    {
        if ($job->status !== 'active') {
            return response()->json(['message' => 'Offre introuvable.', 'data' => null], 404);
        }

        $job->load('company');
        $job->loadCount('applications');

        return new JobResource($job);
    }

    public function store(StoreJobRequest $request): JsonResponse
    {
        $recruteur = $request->user()->recruiter;

        if (!$recruteur?->company_id) {
            return response()->json([
                'message' => 'Vous devez associer votre compte à une entreprise avant de publier une offre.',
                'data'    => null,
            ], 422);
        }

        $offre = $this->jobService->creer($request->validated(), $recruteur);

        return response()->json([
            'message' => 'Offre publiée avec succès.',
            'data'    => new JobResource($offre),
        ], 201);
    }

    public function update(UpdateJobRequest $request, Offre $job): JsonResponse
    {
        $offre = $this->jobService->mettreAJour($job, $request->validated());

        return response()->json([
            'message' => 'Offre mise à jour.',
            'data'    => new JobResource($offre),
        ]);
    }

    public function destroy(Request $request, Offre $job): JsonResponse
    {
        if ($job->recruiter_id !== $request->user()->recruiter?->id) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $job->delete();

        return response()->json(['message' => 'Offre supprimée.', 'data' => null]);
    }

    public function mesOffres(Request $request): AnonymousResourceCollection
    {
        $recruteurId = $request->user()->recruiter?->id ?? 0;

        $offres = Offre::where('recruiter_id', $recruteurId)
            ->withCount('applications')
            ->with('company')
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')
            ->paginate(20);

        return JobResource::collection($offres);
    }

    public function saved(Request $request): JsonResponse
    {
        // À implémenter avec une table pivot job_saves
        return response()->json(['message' => 'Fonctionnalité à venir.', 'data' => []]);
    }

    public function save(Request $request, Offre $job): JsonResponse
    {
        // À implémenter avec une table pivot job_saves
        return response()->json(['message' => 'Offre sauvegardée.', 'data' => null]);
    }
}
