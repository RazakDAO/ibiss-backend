<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'prenom'          => $this->first_name,
            'nom'             => $this->last_name,
            'nom_complet'     => $this->nom_complet,
            'initiales'       => $this->initiales,
            'email'           => $this->email,
            'telephone'       => $this->phone,
            'poste'           => $this->job_title,
            'departement'     => $this->department,
            'lieu_travail'    => $this->work_location,
            'type_contrat'    => $this->contract_type,
            'statut_contrat'  => $this->contract_status,
            'statut_acces'    => $this->access_status,
            'embauche_le'     => $this->hired_at?->toDateString(),
            'fin_contrat'     => $this->end_date?->toDateString(),
            'avatar_url'      => $this->avatar_path
                ? asset('storage/' . $this->avatar_path)
                : null,
            'salaire_base'    => $this->salaire_base ?? 0,
            'cnss_numero'     => $this->cnss_numero,
            'iuts_numero'     => $this->iuts_numero,
            'cree_le'         => $this->created_at->toIso8601String(),
        ];
    }
}
