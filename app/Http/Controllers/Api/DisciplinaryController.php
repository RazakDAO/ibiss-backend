<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Disciplinary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisciplinaryController extends Controller
{
    private function companyId(Request $request): int
    {
        return $request->user()->recruiter->company_id;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $sanctions = Disciplinary::where('company_id', $companyId)
            ->with('employee:id,first_name,last_name,department,avatar_path')
            ->when($request->employee_id, fn ($q, $v) => $q->where('employee_id', $v))
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->when($request->gravite, fn ($q, $v) => $q->where('gravite', $v))
            ->orderByDesc('date_sanction')
            ->paginate(20);

        return response()->json([
            'message' => 'ok',
            'data'    => $sanctions->items(),
            'meta'    => ['total' => $sanctions->total(), 'current_page' => $sanctions->currentPage(), 'last_page' => $sanctions->lastPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'type'          => 'required|in:avertissement_oral,avertissement_ecrit,mise_a_pied,suspension,licenciement',
            'gravite'       => 'required|in:faible,moyen,grave',
            'date_sanction' => 'required|date',
            'motif'         => 'required|string|max:2000',
            'consequences'  => 'nullable|string|max:1000',
        ]);

        $sanction = Disciplinary::create(array_merge($validated, [
            'company_id' => $companyId,
        ]));

        return response()->json([
            'message' => 'Sanction enregistrée.',
            'data'    => $sanction->load('employee:id,first_name,last_name'),
        ], 201);
    }

    public function update(Request $request, Disciplinary $disciplinary): JsonResponse
    {
        if ($disciplinary->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $validated = $request->validate([
            'type'         => 'sometimes|in:avertissement_oral,avertissement_ecrit,mise_a_pied,suspension,licenciement',
            'gravite'      => 'sometimes|in:faible,moyen,grave',
            'motif'        => 'sometimes|string|max:2000',
            'consequences' => 'nullable|string|max:1000',
            'statut'       => 'sometimes|in:actif,archive',
        ]);

        $disciplinary->update($validated);

        return response()->json(['message' => 'Sanction mise à jour.', 'data' => $disciplinary]);
    }
}
