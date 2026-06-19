<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    private function companyId(Request $request): int
    {
        return $request->user()->recruiter->company_id;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $conges = Leave::where('company_id', $companyId)
            ->with('employee:id,first_name,last_name,department,avatar_path')
            ->when($request->statut, fn ($q, $v) => $q->where('statut', $v))
            ->when($request->employee_id, fn ($q, $v) => $q->where('employee_id', $v))
            ->orderByDesc('date_debut')
            ->paginate(20);

        return response()->json([
            'message' => 'ok',
            'data'    => $conges->items(),
            'meta'    => [
                'total'        => $conges->total(),
                'current_page' => $conges->currentPage(),
                'last_page'    => $conges->lastPage(),
                'stats' => [
                    'en_attente' => Leave::where('company_id', $companyId)->where('statut', 'en_attente')->count(),
                    'approuve'   => Leave::where('company_id', $companyId)->where('statut', 'approuve')->count(),
                    'rejete'     => Leave::where('company_id', $companyId)->where('statut', 'rejete')->count(),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type'        => 'required|in:conge_annuel,maladie,maternite,paternite,sans_solde,autre',
            'date_debut'  => 'required|date',
            'date_fin'    => 'required|date|after_or_equal:date_debut',
            'nb_jours'    => 'required|integer|min:1',
            'motif'       => 'nullable|string|max:500',
        ]);

        $conge = Leave::create(array_merge($validated, [
            'company_id' => $companyId,
            'statut'     => 'en_attente',
        ]));

        return response()->json([
            'message' => 'Demande de congé créée.',
            'data'    => $conge->load('employee:id,first_name,last_name'),
        ], 201);
    }

    public function approuver(Request $request, Leave $leave): JsonResponse
    {
        if ($leave->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $leave->update([
            'statut'         => 'approuve',
            'approbateur_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Congé approuvé.', 'data' => $leave]);
    }

    public function rejeter(Request $request, Leave $leave): JsonResponse
    {
        if ($leave->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $validated = $request->validate([
            'motif' => 'nullable|string|max:500',
        ]);

        $leave->update([
            'statut'         => 'rejete',
            'motif_rejet'    => $validated['motif'] ?? null,
            'approbateur_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Congé rejeté.', 'data' => $leave]);
    }
}
