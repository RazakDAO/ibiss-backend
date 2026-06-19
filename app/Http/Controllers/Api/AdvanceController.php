<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvanceController extends Controller
{
    private function companyId(Request $request): int
    {
        return $request->user()->recruiter->company_id;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $avances = Advance::where('company_id', $companyId)
            ->with('employee:id,first_name,last_name,department')
            ->when($request->employee_id, fn ($q, $v) => $q->where('employee_id', $v))
            ->when($request->statut, fn ($q, $v) => $q->where('statut', $v))
            ->orderByDesc('date_demande')
            ->paginate(20);

        return response()->json([
            'message' => 'ok',
            'data'    => $avances->items(),
            'meta'    => [
                'total'            => $avances->total(),
                'current_page'     => $avances->currentPage(),
                'last_page'        => $avances->lastPage(),
                'total_en_attente' => Advance::where('company_id', $companyId)->where('statut', 'en_attente')->sum('montant'),
                'total_approuve'   => Advance::where('company_id', $companyId)->where('statut', 'approuve')->sum('montant'),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'employee_id'               => 'required|exists:employees,id',
            'montant'                   => 'required|integer|min:1000',
            'motif'                     => 'nullable|string|max:500',
            'date_demande'              => 'required|date',
            'date_remboursement_prevue' => 'nullable|date|after:date_demande',
        ]);

        $avance = Advance::create(array_merge($validated, [
            'company_id' => $companyId,
            'statut'     => 'en_attente',
        ]));

        return response()->json([
            'message' => 'Avance sur salaire enregistrée.',
            'data'    => $avance->load('employee:id,first_name,last_name'),
        ], 201);
    }

    public function rembourser(Request $request, Advance $advance): JsonResponse
    {
        if ($advance->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $validated = $request->validate([
            'montant_rembourse' => 'required|integer|min:1',
        ]);

        $nouveauRembourse = $advance->montant_rembourse + $validated['montant_rembourse'];
        $statut = ($nouveauRembourse >= $advance->montant) ? 'rembourse' : 'approuve';

        $advance->update([
            'montant_rembourse' => min($nouveauRembourse, $advance->montant),
            'statut'            => $statut,
        ]);

        return response()->json(['message' => 'Remboursement enregistré.', 'data' => $advance]);
    }

    public function approuver(Request $request, Advance $advance): JsonResponse
    {
        if ($advance->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $advance->update(['statut' => 'approuve']);

        return response()->json(['message' => 'Avance approuvée.', 'data' => $advance]);
    }

    public function rejeter(Request $request, Advance $advance): JsonResponse
    {
        if ($advance->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $advance->update(['statut' => 'rejete']);

        return response()->json(['message' => 'Avance rejetée.', 'data' => $advance]);
    }
}
