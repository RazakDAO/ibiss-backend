<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\Api\RecruiterController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\DisciplinaryController;
use App\Http\Controllers\Api\MedicalVisitController;
use App\Http\Controllers\Api\InternshipController;
use App\Http\Controllers\Api\OvertimeController;
use App\Http\Controllers\Api\AdvanceController;
use App\Http\Controllers\Api\OrgChartController;
use App\Http\Controllers\Api\RhReportController;
use App\Http\Controllers\Api\NotificationController;

Route::prefix('v1')->group(function () {

    // ── Routes publiques ──────────────────────────────────────────────────────

    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('otp/send',   [AuthController::class, 'envoyerOtp'])->middleware('throttle:otp');
        Route::post('otp/verify', [AuthController::class, 'verifierOtp'])->middleware('throttle:otp');
    });

    Route::get('jobs',                [JobController::class, 'index']);
    Route::get('jobs/{job:slug}',    [JobController::class, 'show']);
    Route::get('companies',          [CompanyController::class, 'index']);
    Route::get('companies/{company:slug}', [CompanyController::class, 'show']);

    // Webhook paiement — appelé par Orange Money / Moov Money
    Route::post('payments/callback', [PaymentController::class, 'callback']);

    // ── Routes authentifiées ──────────────────────────────────────────────────

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout']);

        // ── Notifications (tous rôles) ────────────────────────────────────────
        Route::get('notifications',               [NotificationController::class, 'index']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'marquerLue']);
        Route::post('notifications/read-all',     [NotificationController::class, 'toutMarquerLues']);

        // ── Candidat ─────────────────────────────────────────────────────────
        Route::middleware('role:candidate')->group(function () {
            Route::post('jobs/{job}/apply', [ApplicationController::class, 'store'])
                ->middleware('throttle:apply');
            Route::get('applications',         [ApplicationController::class, 'index']);
            Route::get('jobs/saved',           [JobController::class, 'saved']);
            Route::post('jobs/{job}/save',     [JobController::class, 'save']);
            Route::get('profile',              [CandidateController::class, 'show']);
            Route::put('profile',              [CandidateController::class, 'update']);
            Route::post('profile/cv',          [CandidateController::class, 'uploadCv']);
        });

        // ── Recruteur ─────────────────────────────────────────────────────────
        Route::middleware('role:recruiter')->group(function () {

            // Profil recruteur
            Route::get('recruiter/profile', [RecruiterController::class, 'show']);
            Route::put('recruiter/profile', [RecruiterController::class, 'update']);
            Route::post('recruiter/company', [RecruiterController::class, 'attacherEntreprise']);

            // Entreprise
            Route::post('companies',                      [CompanyController::class, 'store']);
            Route::put('companies/{company}',             [CompanyController::class, 'update']);
            Route::post('companies/{company}/logo',       [CompanyController::class, 'uploadLogo']);

            // Offres
            Route::get('recruiter/jobs',          [JobController::class, 'mesOffres']);
            Route::post('jobs',                   [JobController::class, 'store']);
            Route::put('jobs/{job}',              [JobController::class, 'update']);
            Route::delete('jobs/{job}',           [JobController::class, 'destroy']);

            // Candidatures
            Route::get('recruiter/applications',                          [ApplicationController::class, 'parRecruteur']);
            Route::get('jobs/{job}/applications',                         [ApplicationController::class, 'parOffre']);
            Route::patch('applications/{application}/status',             [ApplicationController::class, 'changerStatut']);
            Route::post('applications/{application}/notes',               [ApplicationController::class, 'ajouterNote']);
            Route::get('applications/{application}/export',               [ApplicationController::class, 'exporter']);

            // CVthèque (plan starter+)
            Route::middleware('plan:starter')->group(function () {
                Route::get('candidates',              [CandidateController::class, 'index']);
                Route::get('candidates/{candidate}',  [CandidateController::class, 'profil']);
            });

            // Paiement
            Route::post('payments/initiate', [PaymentController::class, 'initier']);

            // ── Module RH (plan rh+) ──────────────────────────────────────────
            Route::middleware('plan:rh')->prefix('hr')->group(function () {

                // Dashboard & rapports
                Route::get('dashboard',             [EmployeeController::class, 'tableau']);
                Route::get('reports/dashboard',     [RhReportController::class, 'dashboard']);

                // Employés
                Route::get('employees',                             [EmployeeController::class, 'index']);
                Route::post('employees',                            [EmployeeController::class, 'store']);
                Route::get('employees/{employee}',                  [EmployeeController::class, 'show']);
                Route::put('employees/{employee}',                  [EmployeeController::class, 'update']);
                Route::delete('employees/{employee}',               [EmployeeController::class, 'destroy']);
                Route::get('employees/{employee}/payslips',         [PayrollController::class, 'bulletinsEmploye']);
                Route::post('employees/{employee}/attestation',     [RhReportController::class, 'attestation']);

                // Congés
                Route::get('leaves',                        [LeaveController::class, 'index']);
                Route::post('leaves',                       [LeaveController::class, 'store']);
                Route::patch('leaves/{leave}/approve',      [LeaveController::class, 'approuver']);
                Route::patch('leaves/{leave}/reject',       [LeaveController::class, 'rejeter']);

                // Paie
                Route::get('payroll',                       [PayrollController::class, 'index']);
                Route::post('payroll/generate',             [PayrollController::class, 'generer']);
                Route::get('payroll/syscohada-export',      [PayrollController::class, 'exportSYSCOHADA']);
                Route::get('payroll/{payroll}/payslip',     [PayrollController::class, 'bulletin']);
                Route::patch('payroll/{payroll}/validate',  [PayrollController::class, 'valider']);

                // Évaluations
                Route::get('evaluations',               [EvaluationController::class, 'index']);
                Route::post('evaluations',              [EvaluationController::class, 'store']);
                Route::put('evaluations/{evaluation}',  [EvaluationController::class, 'update']);

                // ── Fonctionnalités Entreprise ──────────────────────────────
                Route::middleware('plan:enterprise')->group(function () {

                    // Présences & planning
                    Route::get('attendances',           [AttendanceController::class, 'index']);
                    Route::post('attendances',          [AttendanceController::class, 'store']);
                    Route::get('schedules',             [AttendanceController::class, 'planning']);
                    Route::post('schedules',            [AttendanceController::class, 'creerPlanning']);

                    // Disciplinaire
                    Route::get('disciplinary',                      [DisciplinaryController::class, 'index']);
                    Route::post('disciplinary',                     [DisciplinaryController::class, 'store']);
                    Route::put('disciplinary/{disciplinary}',       [DisciplinaryController::class, 'update']);

                    // Visites médicales
                    Route::get('medical-visits',                        [MedicalVisitController::class, 'index']);
                    Route::post('medical-visits',                       [MedicalVisitController::class, 'store']);
                    Route::put('medical-visits/{medicalVisit}',         [MedicalVisitController::class, 'update']);

                    // Organigramme
                    Route::get('org-chart', [OrgChartController::class, 'index']);

                    // Stages & apprentissages
                    Route::get('internships',               [InternshipController::class, 'index']);
                    Route::post('internships',              [InternshipController::class, 'store']);
                    Route::put('internships/{internship}',  [InternshipController::class, 'update']);
                    Route::delete('internships/{internship}', [InternshipController::class, 'destroy']);

                    // Heures supplémentaires
                    Route::get('overtime',                  [OvertimeController::class, 'index']);
                    Route::post('overtime',                 [OvertimeController::class, 'store']);
                    Route::patch('overtime/{overtime}/approve', [OvertimeController::class, 'approuver']);

                    // Avances sur salaire
                    Route::get('advances',                      [AdvanceController::class, 'index']);
                    Route::post('advances',                     [AdvanceController::class, 'store']);
                    Route::patch('advances/{advance}/approve',  [AdvanceController::class, 'approuver']);
                    Route::patch('advances/{advance}/reject',   [AdvanceController::class, 'rejeter']);
                    Route::patch('advances/{advance}/repay',    [AdvanceController::class, 'rembourser']);
                });
            });
        });

        // ── Admin ─────────────────────────────────────────────────────────────
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('dashboard',                         [AdminController::class, 'dashboard']);
            Route::get('jobs/pending',                      [AdminController::class, 'offresEnAttente']);
            Route::patch('jobs/{job}/approve',              [AdminController::class, 'approuver']);
            Route::patch('jobs/{job}/reject',               [AdminController::class, 'rejeter']);
            Route::patch('companies/{company}/verify',      [AdminController::class, 'verifierEntreprise']);
        });
    });
});

Route::get('/ping', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'L\'API Laravel répond parfaitement !'
    ]);
});