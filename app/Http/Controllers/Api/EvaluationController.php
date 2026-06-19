<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    private function companyId(Request $request): int
    {
        return $request->user()->recruiter->company_id;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $evaluations = Evaluation::where('company_id', $companyId)
            ->with('employee:id,first_name,last_name,department,job_title,avatar_path')
            ->when($request->employee_id, fn ($q, $v) => $q->where('employee_id', $v))
            ->when($request->statut, fn ($q, $v) => $q->where('statut', $v))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'message' => 'ok',
            'data'    => $evaluations->items(),
            'meta'    => ['total' => $evaluations->total(), 'current_page' => $evaluations->currentPage(), 'last_page' => $evaluations->lastPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'periode'     => 'required|string|max:100',
            'note'        => 'required|integer|min:1|max:5',
            'objectifs'   => 'nullable|string|max:2000',
            'commentaire' => 'nullable|string|max:2000',
            'statut'      => 'sometimes|in:brouillon,finalise',
        ]);

        $evaluation = Evaluation::create(array_merge($validated, [
            'company_id'    => $companyId,
            'evaluateur_id' => $request->user()->id,
        ]));

        return response()->json([
            'message' => 'Évaluation créée.',
            'data'    => $evaluation->load('employee:id,first_name,last_name'),
        ], 201);
    }

    public function update(Request $request, Evaluation $evaluation): JsonResponse
    {
        if ($evaluation->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $validated = $request->validate([
            'note'        => 'sometimes|integer|min:1|max:5',
            'objectifs'   => 'nullable|string|max:2000',
            'commentaire' => 'nullable|string|max:2000',
            'statut'      => 'sometimes|in:brouillon,finalise',
        ]);

        $evaluation->update($validated);

        return response()->json(['message' => 'Évaluation mise à jour.', 'data' => $evaluation]);
    }
}
