<?php

namespace App\Services;

use App\Models\Offre;
use App\Models\Recruiter;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class JobService
{
    public function lister(array $filtres): LengthAwarePaginator
    {
        $query = Offre::with('company')
            ->withCount('applications')
            ->active()
            ->orderByDesc('is_sponsored')
            ->orderByDesc('created_at');

        if (!empty($filtres['q'])) {
            $query->where(function ($q) use ($filtres) {
                $q->where('title', 'like', "%{$filtres['q']}%")
                  ->orWhere('description', 'like', "%{$filtres['q']}%");
            });
        }

        if (!empty($filtres['sector'])) {
            $query->where('sector', $filtres['sector']);
        }

        if (!empty($filtres['city'])) {
            $query->where('city', $filtres['city']);
        }

        if (!empty($filtres['type'])) {
            $query->where('type', $filtres['type']);
        }

        if (!empty($filtres['level'])) {
            $query->where('level', $filtres['level']);
        }

        return $query->paginate(15);
    }

    public function creer(array $donnees, Recruiter $recruteur): Offre
    {
        return Offre::create([
            ...$donnees,
            'company_id'   => $recruteur->company_id,
            'recruiter_id' => $recruteur->id,
            'slug'         => Str::slug($donnees['title']) . '-' . Str::random(6),
            'status'       => 'active',
        ]);
    }

    public function mettreAJour(Offre $offre, array $donnees): Offre
    {
        if (isset($donnees['title'])) {
            $donnees['slug'] = Str::slug($donnees['title']) . '-' . Str::random(6);
        }

        $offre->update($donnees);
        return $offre->fresh();
    }
}
