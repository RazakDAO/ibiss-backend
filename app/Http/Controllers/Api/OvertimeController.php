<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Overtime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    private function companyId(Request $request): int
    {
        return $request->user()->recruiter->company_id;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $heuresSupp = Overtime::where('company_id', $companyId)
            ->with('employee:id,first_name,last_name,department,salaire_base')
            ->when($request->employee_id, fn ($q, $v) => $q->where('employee_id', $v))
            ->when($request->statut, fn ($q, $v) => $q->where('statut', $v))
            ->orderByDesc('date')
            ->paginate(20);

        return response()->json([
            'message' => 'ok',
            'data'    => $heuresSupp->items(),
            'meta'    => ['total' => $heuresSupp->total(), 'current_page' => $heuresSupp->currentPage(), 'last_page' => $heuresSupp->lastPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'date'           => 'required|date',
            'nb_heures'      => 'required|numeric|min:0.5|max:24',
            'motif'          => 'nullable|string|max:500',
            'taux_majoration' => 'sometimes|integer|in:125,150,200',
        ]);

        // Calculer le montant automatiquement
        $employe = Employee::find($validated['employee_id']);
        $tauxHoraire = ($employe->salaire_base ?? 0) / (30 * 8); // base journalière / 8h
        $taux = $validated['taux_majoration'] ?? 125;
        $montant = (int) round($tauxHoraire * $validated['nb_heures'] * ($taux / 100));

        $hs = Overtime::create(array_merge($validated, [
            'company_id'     => $companyId,
            'taux_majoration' => $taux,
            'montant'        => $montant,
            'statut'         => 'en_attente',
        ]));

        return response()->json([
            'message' => 'Heures supplémentaires enregistrées.',
            'data'    => $hs->load('employee:id,first_name,last_name'),
        ], 201);
    }

    public function approuver(Request $request, Overtime $overtime): JsonResponse
    {
        if ($overtime->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $overtime->update(['statut' => 'approuve']);

        return response()->json(['message' => 'Heures supplémentaires approuvées.', 'data' => $overtime]);
    }
}
