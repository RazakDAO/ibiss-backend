<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendJobAlerts;
use App\Models\Application;
use App\Models\Company;
use App\Models\Offre;
use App\Models\Payment;
use App\Models\Recruiter;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $debutMois = now()->startOfMonth();

        $stats = [
            'offres' => [
                'total'    => Offre::count(),
                'pending'  => Offre::where('status', 'pending')->count(),
                'active'   => Offre::where('status', 'active')->count(),
                'ce_mois'  => Offre::where('created_at', '>=', $debutMois)->count(),
            ],
            'candidats' => [
                'total'   => User::where('role', 'candidate')->count(),
                'ce_mois' => User::where('role', 'candidate')->where('created_at', '>=', $debutMois)->count(),
            ],
            'recruteurs' => [
                'total'    => User::where('role', 'recruiter')->count(),
                'par_plan' => Recruiter::select('plan', DB::raw('count(*) as total'))
                    ->groupBy('plan')
                    ->pluck('total', 'plan'),
            ],
            'candidatures' => [
                'total'         => Application::count(),
                'ce_mois'       => Application::where('created_at', '>=', $debutMois)->count(),
                'selectionnes'  => Application::where('status', 'selected')->count(),
            ],
            'paiements' => [
                'revenus_mois' => Payment::where('status', Payment::STATUS_COMPLETED)
                    ->where('created_at', '>=', $debutMois)
                    ->sum('amount'),
                'par_operateur' => Payment::where('status', Payment::STATUS_COMPLETED)
                    ->select('provider', DB::raw('sum(amount) as total'))
                    ->groupBy('provider')
                    ->pluck('total', 'provider'),
            ],
            'entreprises' => [
                'total'     => Company::count(),
                'verifiees' => Company::where('is_verified', true)->count(),
                'en_attente' => Company::where('is_verified', false)->count(),
            ],
        ];

        return response()->json([
            'message' => 'Tableau de bord administrateur.',
            'data'    => $stats,
        ]);
    }

    public function offresEnAttente(Request $request): JsonResponse
    {
        $offres = Offre::with(['recruiter.user', 'company'])
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->paginate(20);

        return response()->json([
            'message' => 'Offres en attente de modération.',
            'data'    => $offres->items(),
            'meta'    => [
                'total'        => $offres->total(),
                'current_page' => $offres->currentPage(),
                'last_page'    => $offres->lastPage(),
            ],
        ]);
    }

    public function approuver(Request $request, Offre $job): JsonResponse
    {
        if ($job->status !== 'pending') {
            return response()->json([
                'message' => 'Cette offre n\'est pas en attente de modération.',
                'data'    => null,
            ], 422);
        }

        $job->update(['status' => 'active']);

        SendJobAlerts::dispatch($job)->onQueue('notifications');

        Log::info('Offre approuvée', [
            'job_id'   => $job->id,
            'admin_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Offre approuvée et publiée.',
            'data'    => ['id' => $job->id, 'status' => 'active'],
        ]);
    }

    public function rejeter(Request $request, Offre $job): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $job->update(['status' => 'archived']);

        // Notifier le recruteur par email
        if ($job->recruiter?->user?->email) {
            $motif = $request->reason ?? 'Offre non conforme aux conditions d\'utilisation.';
            \Illuminate\Support\Facades\Mail::raw(
                "Votre offre \"{$job->title}\" a été rejetée.\n\nMotif : {$motif}\n\nCordialement,\nL'équipe Ibissé",
                function ($message) use ($job) {
                    $message->to($job->recruiter->user->email)
                            ->subject("Offre rejetée : {$job->title}");
                }
            );
        }

        Log::info('Offre rejetée', [
            'job_id'   => $job->id,
            'admin_id' => $request->user()->id,
            'reason'   => $request->reason,
        ]);

        return response()->json([
            'message' => 'Offre rejetée.',
            'data'    => ['id' => $job->id, 'status' => 'archived'],
        ]);
    }

    public function verifierEntreprise(Request $request, Company $company): JsonResponse
    {
        if ($company->is_verified) {
            return response()->json([
                'message' => 'Cette entreprise est déjà vérifiée.',
                'data'    => null,
            ], 422);
        }

        $company->update(['is_verified' => true]);

        Log::info('Entreprise vérifiée', [
            'company_id' => $company->id,
            'admin_id'   => $request->user()->id,
        ]);

        return response()->json([
            'message' => "Entreprise \"{$company->name}\" vérifiée avec succès.",
            'data'    => ['id' => $company->id, 'is_verified' => true],
        ]);
    }
}
