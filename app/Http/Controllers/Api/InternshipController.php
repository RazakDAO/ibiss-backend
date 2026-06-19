<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternshipController extends Controller
{
    private function companyId(Request $request): int
    {
        return $request->user()->recruiter->company_id;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $stages = Internship::where('company_id', $companyId)
            ->with('tuteur:id,first_name,last_name')
            ->when($request->statut, fn ($q, $v) => $q->where('statut', $v))
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->orderByDesc('date_debut')
            ->paginate(20);

        return response()->json([
            'message' => 'ok',
            'data'    => $stages->items(),
            'meta'    => ['total' => $stages->total(), 'current_page' => $stages->currentPage(), 'last_page' => $stages->lastPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'nom_complet'  => 'required|string|max:150',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:20',
            'ecole'        => 'nullable|string|max:150',
            'type'         => 'required|in:stage,apprentissage',
            'poste'        => 'required|string|max:150',
            'departement'  => 'nullable|string|max:100',
            'tuteur_id'    => 'nullable|exists:employees,id',
            'date_debut'   => 'required|date',
            'date_fin'     => 'required|date|after:date_debut',
            'gratification' => 'nullable|integer|min:0',
            'objectifs'    => 'nullable|string|max:2000',
        ]);

        $stage = Internship::create(array_merge($validated, [
            'company_id' => $companyId,
            'statut'     => 'en_cours',
        ]));

        return response()->json([
            'message' => 'Stage/apprentissage créé.',
            'data'    => $stage,
        ], 201);
    }

    public function update(Request $request, Internship $internship): JsonResponse
    {
        if ($internship->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $validated = $request->validate([
            'statut'       => 'sometimes|in:en_cours,termine,annule',
            'gratification' => 'nullable|integer|min:0',
            'objectifs'    => 'nullable|string|max:2000',
            'tuteur_id'    => 'nullable|exists:employees,id',
            'date_fin'     => 'nullable|date',
        ]);

        $internship->update($validated);

        return response()->json(['message' => 'Stage mis à jour.', 'data' => $internship]);
    }

    public function destroy(Request $request, Internship $internship): JsonResponse
    {
        if ($internship->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $internship->delete();

        return response()->json(['message' => 'Stage supprimé.', 'data' => null]);
    }
}
