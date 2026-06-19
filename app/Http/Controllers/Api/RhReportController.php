<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advance;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\Leave;
use App\Models\Payroll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RhReportController extends Controller
{
    private function companyId(Request $request): int
    {
        return $request->user()->recruiter->company_id;
    }

    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);
        $mois  = $request->query('mois', now()->month);
        $annee = $request->query('annee', now()->year);

        $employes = Employee::where('company_id', $companyId);

        $stats = [
            'effectif' => [
                'total'       => $employes->count(),
                'actifs'      => $employes->where('access_status', 'actif')->count(),
                'contrats_cdi' => (clone $employes)->where('contract_type', 'cdi')->count(),
                'contrats_cdd' => (clone $employes)->where('contract_type', 'cdd')->count(),
            ],
            'conges' => [
                'en_attente' => Leave::where('company_id', $companyId)->where('statut', 'en_attente')->count(),
                'ce_mois'    => Leave::where('company_id', $companyId)
                    ->whereYear('date_debut', $annee)
                    ->whereMonth('date_debut', $mois)
                    ->count(),
            ],
            'paie' => [
                'masse_salariale' => Payroll::where('company_id', $companyId)
                    ->where('mois', $mois)->where('annee', $annee)
                    ->sum('net_a_payer'),
                'bulletins_valides' => Payroll::where('company_id', $companyId)
                    ->where('mois', $mois)->where('annee', $annee)
                    ->where('statut', 'valide')->count(),
            ],
            'evaluations' => [
                'note_moyenne' => round(Evaluation::where('company_id', $companyId)
                    ->where('statut', 'finalise')->avg('note') ?? 0, 1),
                'ce_trimestre' => Evaluation::where('company_id', $companyId)
                    ->whereYear('created_at', $annee)->count(),
            ],
            'avances' => [
                'en_attente' => Advance::where('company_id', $companyId)->where('statut', 'en_attente')->count(),
                'montant_total_du' => Advance::where('company_id', $companyId)
                    ->whereIn('statut', ['approuve'])
                    ->selectRaw('SUM(montant - montant_rembourse) as restant')
                    ->value('restant') ?? 0,
            ],
        ];

        return response()->json(['message' => 'ok', 'data' => $stats]);
    }

    public function attestation(Request $request, Employee $employee): JsonResponse
    {
        if ($employee->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        // Dans une implémentation réelle, on génèrerait un PDF ici (ex: avec Laravel-PDF / DomPDF)
        // Pour l'instant, on retourne les données nécessaires au PDF côté client ou pour la génération
        return response()->json([
            'message' => 'Données attestation générées.',
            'data'    => [
                'employe'       => [
                    'nom_complet'  => $employee->first_name . ' ' . $employee->last_name,
                    'poste'        => $employee->job_title,
                    'departement'  => $employee->department,
                    'date_embauche' => $employee->hired_at,
                    'contrat'      => $employee->contract_type,
                ],
                'date_emission' => now()->toDateString(),
                'reference'     => 'ATT-' . $employee->id . '-' . now()->format('YmdHis'),
            ],
        ]);
    }
}
