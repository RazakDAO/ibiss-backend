<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    private function companyId(Request $request): int
    {
        return $request->user()->recruiter->company_id;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $pointages = Attendance::where('company_id', $companyId)
            ->with('employee:id,first_name,last_name,department,avatar_path')
            ->when($request->date, fn ($q, $v) => $q->whereDate('date', $v))
            ->when($request->employee_id, fn ($q, $v) => $q->where('employee_id', $v))
            ->when($request->statut, fn ($q, $v) => $q->where('statut', $v))
            ->orderByDesc('date')
            ->paginate(30);

        return response()->json([
            'message' => 'ok',
            'data'    => $pointages->items(),
            'meta'    => ['total' => $pointages->total(), 'current_page' => $pointages->currentPage(), 'last_page' => $pointages->lastPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'date'          => 'required|date',
            'heure_arrivee' => 'nullable|date_format:H:i',
            'heure_depart'  => 'nullable|date_format:H:i|after:heure_arrivee',
            'statut'        => 'required|in:present,absent,retard,demi_journee,conge,ferie',
            'note'          => 'nullable|string|max:255',
        ]);

        // Calculer nb_heures automatiquement
        if (!empty($validated['heure_arrivee']) && !empty($validated['heure_depart'])) {
            $debut = \Carbon\Carbon::createFromTimeString($validated['heure_arrivee']);
            $fin   = \Carbon\Carbon::createFromTimeString($validated['heure_depart']);
            $validated['nb_heures'] = round($debut->diffInMinutes($fin) / 60, 2);
        }

        $pointage = Attendance::updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'date' => $validated['date']],
            array_merge($validated, ['company_id' => $companyId])
        );

        return response()->json([
            'message' => 'Pointage enregistré.',
            'data'    => $pointage->load('employee:id,first_name,last_name'),
        ], 201);
    }

    public function planning(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $plannings = Schedule::where('company_id', $companyId)
            ->with('employee:id,first_name,last_name,department')
            ->when($request->semaine, fn ($q, $v) => $q->where('semaine_debut', $v))
            ->orderBy('semaine_debut')
            ->paginate(30);

        return response()->json([
            'message' => 'ok',
            'data'    => $plannings->items(),
            'meta'    => ['total' => $plannings->total(), 'current_page' => $plannings->currentPage(), 'last_page' => $plannings->lastPage()],
        ]);
    }

    public function creerPlanning(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'semaine_debut' => 'required|date',
            'horaires'      => 'required|array',
        ]);

        $planning = Schedule::updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'semaine_debut' => $validated['semaine_debut']],
            array_merge($validated, ['company_id' => $companyId])
        );

        return response()->json([
            'message' => 'Planning enregistré.',
            'data'    => $planning->load('employee:id,first_name,last_name'),
        ], 201);
    }
}
