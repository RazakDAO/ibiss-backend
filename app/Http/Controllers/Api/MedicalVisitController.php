<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicalVisitController extends Controller
{
    private function companyId(Request $request): int
    {
        return $request->user()->recruiter->company_id;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $visites = MedicalVisit::where('company_id', $companyId)
            ->with('employee:id,first_name,last_name,department,avatar_path')
            ->when($request->employee_id, fn ($q, $v) => $q->where('employee_id', $v))
            ->when($request->resultat, fn ($q, $v) => $q->where('resultat', $v))
            ->orderByDesc('date_visite')
            ->paginate(20);

        // Alertes : prochaine visite dans moins de 30 jours
        $alertes = MedicalVisit::where('company_id', $companyId)
            ->whereNotNull('date_prochaine_visite')
            ->whereBetween('date_prochaine_visite', [now(), now()->addDays(30)])
            ->with('employee:id,first_name,last_name')
            ->count();

        return response()->json([
            'message' => 'ok',
            'data'    => $visites->items(),
            'meta'    => [
                'total'        => $visites->total(),
                'current_page' => $visites->currentPage(),
                'last_page'    => $visites->lastPage(),
                'alertes_30j'  => $alertes,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'employee_id'           => 'required|exists:employees,id',
            'date_visite'           => 'required|date',
            'date_prochaine_visite' => 'nullable|date|after:date_visite',
            'medecin'               => 'nullable|string|max:150',
            'lieu'                  => 'nullable|string|max:255',
            'resultat'              => 'nullable|in:apte,inapte,apte_reserves',
            'observations'          => 'nullable|string|max:2000',
        ]);

        $visite = MedicalVisit::create(array_merge($validated, [
            'company_id' => $companyId,
        ]));

        return response()->json([
            'message' => 'Visite médicale enregistrée.',
            'data'    => $visite->load('employee:id,first_name,last_name'),
        ], 201);
    }

    public function update(Request $request, MedicalVisit $medicalVisit): JsonResponse
    {
        if ($medicalVisit->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $validated = $request->validate([
            'date_prochaine_visite' => 'nullable|date',
            'resultat'              => 'nullable|in:apte,inapte,apte_reserves',
            'observations'          => 'nullable|string|max:2000',
        ]);

        $medicalVisit->update($validated);

        return response()->json(['message' => 'Visite mise à jour.', 'data' => $medicalVisit]);
    }
}
