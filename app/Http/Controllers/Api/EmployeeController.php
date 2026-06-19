<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    private function companyId(Request $request): int
    {
        return $request->user()->recruiter->company_id;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $companyId = $this->companyId($request);

        $employes = Employee::where('company_id', $companyId)
            ->when($request->q, function ($query, $q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('first_name', 'ilike', "%{$q}%")
                        ->orWhere('last_name', 'ilike', "%{$q}%")
                        ->orWhere('job_title', 'ilike', "%{$q}%")
                        ->orWhere('department', 'ilike', "%{$q}%");
                });
            })
            ->when($request->statut_contrat, fn ($q, $v) => $q->where('contract_status', $v))
            ->when($request->departement, fn ($q, $v) => $q->where('department', $v))
            ->orderByDesc('hired_at')
            ->paginate(20);

        return EmployeeResource::collection($employes);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $employe = Employee::create(array_merge(
            $request->validated(),
            ['company_id' => $companyId]
        ));

        return response()->json([
            'message' => 'Employé ajouté avec succès.',
            'data'    => new EmployeeResource($employe),
        ], 201);
    }

    public function show(Request $request, Employee $employee): JsonResponse
    {
        if ($employee->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        return response()->json([
            'message' => 'ok',
            'data'    => new EmployeeResource($employee),
        ]);
    }

    public function update(StoreEmployeeRequest $request, Employee $employee): JsonResponse
    {
        if ($employee->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $employee->update($request->validated());

        return response()->json([
            'message' => 'Employé mis à jour.',
            'data'    => new EmployeeResource($employee),
        ]);
    }

    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        if ($employee->company_id !== $this->companyId($request)) {
            return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
        }

        $employee->delete();

        return response()->json(['message' => 'Employé archivé.', 'data' => null]);
    }

    public function tableau(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $total     = Employee::where('company_id', $companyId)->count();
        $actifs    = Employee::where('company_id', $companyId)->where('access_status', 'actif')->count();
        $aujourdhui = Employee::where('company_id', $companyId)
            ->whereDate('hired_at', today())
            ->get(['first_name', 'last_name', 'job_title', 'hired_at', 'avatar_path']);

        $recents = Employee::where('company_id', $companyId)
            ->orderByDesc('hired_at')
            ->limit(5)
            ->get();

        return response()->json([
            'message' => 'ok',
            'data'    => [
                'stats' => [
                    'total'  => $total,
                    'actifs' => $actifs,
                    'en_cours' => Employee::where('company_id', $companyId)
                        ->where('contract_status', 'en_cours')->count(),
                    'en_attente' => Employee::where('company_id', $companyId)
                        ->where('access_status', 'invite')->count(),
                ],
                'arrivees_aujourd_hui' => EmployeeResource::collection($aujourdhui),
                'recents'              => EmployeeResource::collection($recents),
            ],
        ]);
    }
}
