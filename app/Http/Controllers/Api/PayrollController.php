<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advance;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Payroll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    private function companyId(Request $request): int
    {
        return $request->user()->recruiter->company_id;
    }

    // Calcul IUTS Burkina Faso (tranches DGI mensuelles)
    private function calculerIuts(int $baseTaxable): int
    {
        $tranches = [
            [0,        7500,  0.00],
            [7501,     12500, 0.07],
            [12501,    21000, 0.14],
            [21001,    35000, 0.19],
            [35001,    70000, 0.24],
            [70001,    175000, 0.28],
            [175001,   PHP_INT_MAX, 0.325],
        ];

        $iuts = 0;
        foreach ($tranches as [$min, $max, $taux]) {
            if ($baseTaxable <= $min) break;
            $imposable = min($baseTaxable, $max) - $min;
            $iuts += $imposable * $taux;
        }

        return (int) round($iuts);
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $bulletins = Payroll::where('company_id', $companyId)
            ->with('employee:id,first_name,last_name,department')
            ->when($request->annee, fn ($q, $v) => $q->where('annee', $v))
            ->when($request->mois, fn ($q, $v) => $q->where('mois', $v))
            ->when($request->statut, fn ($q, $v) => $q->where('statut', $v))
            ->orderByDesc('annee')->orderByDesc('mois')
            ->paginate(20);

        return response()->json([
            'message' => 'ok',
            'data'    => $bulletins->items(),
            'meta'    => [
                'total'        => $bulletins->total(),
                'current_page' => $bulletins->currentPage(),
                'last_page'    => $bulletins->lastPage(),
            ],
        ]);
    }

    public function generer(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'mois'  => 'required|integer|min:1|max:12',
            'annee' => 'required|integer|min:2020|max:2100',
        ]);

        $employes = Employee::where('company_id', $companyId)
            ->where('contract_status', 'en_cours')
            ->get();

        $bulletinsGeneres = 0;

        foreach ($employes as $employe) {
            // Éviter les doublons
            if (Payroll::where('employee_id', $employe->id)
                ->where('mois', $validated['mois'])
                ->where('annee', $validated['annee'])
                ->exists()) {
                continue;
            }

            $salaireBrut = $employe->salaire_base ?? 0;

            // Cotisations CNSS
            $cnssPatronal  = (int) round($salaireBrut * 0.16);
            $cnssSalarial  = (int) round($salaireBrut * 0.055);

            // Abattement professionnel 20% puis IUTS
            $baseTaxable = max(0, (int) round(($salaireBrut - $cnssSalarial) * 0.80));
            $iuts = $this->calculerIuts($baseTaxable);

            // Heures supplémentaires approuvées du mois
            $heuresSuppMontant = Overtime::where('employee_id', $employe->id)
                ->whereYear('date', $validated['annee'])
                ->whereMonth('date', $validated['mois'])
                ->where('statut', 'approuve')
                ->sum('montant');

            // Avances à déduire
            $avancesDeduites = Advance::where('employee_id', $employe->id)
                ->where('statut', 'approuve')
                ->whereNull('date_remboursement_prevue')
                ->sum('montant');

            $netAPayer = $salaireBrut - $cnssSalarial - $iuts + $heuresSuppMontant - $avancesDeduites;

            Payroll::create([
                'employee_id'          => $employe->id,
                'company_id'           => $companyId,
                'genere_par'           => $request->user()->id,
                'mois'                 => $validated['mois'],
                'annee'                => $validated['annee'],
                'salaire_brut'         => $salaireBrut,
                'cnss_patronal'        => $cnssPatronal,
                'cnss_salarial'        => $cnssSalarial,
                'iuts'                 => $iuts,
                'heures_supp_montant'  => $heuresSuppMontant,
                'avances_deduites'     => $avancesDeduites,
                'net_a_payer'          => max(0, $netAPayer),
                'statut'               => 'brouillon',
            ]);

            $bulletinsGeneres++;
        }

        return response()->json([
            'message' => "{$bulletinsGeneres} bulletin(s) généré(s) pour {$validated['mois']}/{$validated['annee']}.",
            'data'    => ['bulletins_generes' => $bulletinsGeneres],
        ]);
    }

    public function bulletin(Request $request, Payroll $payroll): JsonResponse
    {
        if ($payroll->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        return response()->json([
            'message' => 'ok',
            'data'    => $payroll->load('employee'),
        ]);
    }

    public function valider(Request $request, Payroll $payroll): JsonResponse
    {
        if ($payroll->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $payroll->update(['statut' => 'valide']);

        return response()->json(['message' => 'Bulletin validé.', 'data' => $payroll]);
    }

    public function exportSYSCOHADA(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);
        $mois  = $request->query('mois');
        $annee = $request->query('annee');

        $bulletins = Payroll::where('company_id', $companyId)
            ->when($mois, fn ($q, $v) => $q->where('mois', $v))
            ->when($annee, fn ($q, $v) => $q->where('annee', $v))
            ->where('statut', '!=', 'brouillon')
            ->with('employee')
            ->get();

        $totalBrut      = $bulletins->sum('salaire_brut');
        $totalPatronal  = $bulletins->sum('cnss_patronal');
        $totalSalarial  = $bulletins->sum('cnss_salarial');
        $totalIuts      = $bulletins->sum('iuts');
        $totalNet       = $bulletins->sum('net_a_payer');

        return response()->json([
            'message' => 'ok',
            'data'    => [
                'periode'         => "{$mois}/{$annee}",
                'bulletins'       => $bulletins,
                'totaux'          => [
                    'salaire_brut'  => $totalBrut,
                    'cnss_patronal' => $totalPatronal,
                    'cnss_salarial' => $totalSalarial,
                    'iuts'          => $totalIuts,
                    'net_a_payer'   => $totalNet,
                    'charge_totale' => $totalBrut + $totalPatronal,
                ],
            ],
        ]);
    }

    public function bulletinsEmploye(Request $request, Employee $employee): JsonResponse
    {
        if ($employee->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $bulletins = Payroll::where('employee_id', $employee->id)
            ->orderByDesc('annee')->orderByDesc('mois')
            ->get();

        return response()->json(['message' => 'ok', 'data' => $bulletins]);
    }
}
