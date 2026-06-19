<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrgChartController extends Controller
{
    private function companyId(Request $request): int
    {
        return $request->user()->recruiter->company_id;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        // Grouper les employés par département
        $employes = Employee::where('company_id', $companyId)
            ->where('contract_status', 'en_cours')
            ->select('id', 'first_name', 'last_name', 'job_title', 'department', 'avatar_path', 'hired_at')
            ->orderBy('department')
            ->orderBy('job_title')
            ->get();

        // Construire l'organigramme par département
        $departements = $employes->groupBy('department')->map(function ($membres, $dept) {
            return [
                'departement' => $dept ?? 'Non assigné',
                'total'       => $membres->count(),
                'membres'     => $membres->map(fn ($e) => [
                    'id'         => $e->id,
                    'nom_complet' => $e->first_name . ' ' . $e->last_name,
                    'poste'      => $e->job_title,
                    'avatar'     => $e->avatar_path,
                ]),
            ];
        })->values();

        return response()->json([
            'message' => 'ok',
            'data'    => [
                'total_employes' => $employes->count(),
                'departements'   => $departements,
            ],
        ]);
    }
}
