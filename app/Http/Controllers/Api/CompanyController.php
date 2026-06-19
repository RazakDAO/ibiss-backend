<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companies = Company::withCount('offres')
            ->when($request->q, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($request->sector, fn ($q, $v) => $q->where('sector', $v))
            ->when($request->city, fn ($q, $v) => $q->where('city', $v))
            ->when($request->verified, fn ($q) => $q->where('is_verified', true))
            ->orderByDesc('is_verified')
            ->orderBy('name')
            ->paginate(15);

        return response()->json([
            'message' => 'Liste des entreprises.',
            'data'    => CompanyResource::collection($companies),
            'meta'    => [
                'total'        => $companies->total(),
                'current_page' => $companies->currentPage(),
                'last_page'    => $companies->lastPage(),
            ],
        ]);
    }

    public function show(Company $company): JsonResponse
    {
        $company->loadCount('offres');

        return response()->json([
            'message' => 'Détail entreprise.',
            'data'    => new CompanyResource($company),
        ]);
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $recruteur = $request->user()->recruiter;

        if (! $recruteur) {
            return response()->json([
                'message' => 'Profil recruteur introuvable.',
                'data'    => null,
            ], 422);
        }

        if ($recruteur->company_id) {
            return response()->json([
                'message' => 'Vous êtes déjà associé à une entreprise.',
                'data'    => null,
            ], 422);
        }

        $company = Company::create($request->validated());
        $recruteur->update(['company_id' => $company->id]);

        return response()->json([
            'message' => 'Entreprise créée. Elle sera vérifiée par notre équipe.',
            'data'    => new CompanyResource($company),
        ], 201);
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $company->update($request->validated());

        return response()->json([
            'message' => 'Entreprise mise à jour.',
            'data'    => new CompanyResource($company->refresh()),
        ]);
    }

    public function uploadLogo(Request $request, Company $company): JsonResponse
    {
        $request->validate([
            'logo' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'logo.mimes' => 'Le logo doit être au format JPG, PNG ou WebP.',
            'logo.max'   => 'Le logo ne doit pas dépasser 2 Mo.',
        ]);

        if ($request->user()->role !== 'admin') {
            $recruteur = $request->user()->recruiter;
            if (! $recruteur || $recruteur->company_id !== $company->id) {
                return response()->json(['message' => 'Action non autorisée.', 'data' => null], 403);
            }
        }

        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }

        $chemin = $request->file('logo')->storeAs(
            'logos',
            'logo_' . $company->id . '_' . time() . '.' . $request->file('logo')->extension(),
            'public'
        );

        $company->update(['logo_path' => $chemin]);

        return response()->json([
            'message' => 'Logo mis à jour.',
            'data'    => ['logo_url' => url(Storage::url($chemin))],
        ]);
    }
}
