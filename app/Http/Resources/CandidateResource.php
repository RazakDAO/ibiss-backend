<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CandidateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $estProprietaire = $request->user()?->id === $this->user_id;
        $estRecruteur    = $request->user()?->role === 'recruiter';

        return [
            'id'          => $this->id,
            'name'        => $this->user->name,
            'title'       => $this->title,
            'bio'         => $this->bio,
            'city'        => $this->city,
            'visibility'  => $this->when($estProprietaire, $this->visibility),
            'skills'      => $this->skills ?? [],
            'experiences' => $this->experiences ?? [],
            'education'   => $this->education ?? [],
            'cv_url'      => $this->when(
                $estProprietaire || $estRecruteur,
                $this->cv_path ? Storage::url($this->cv_path) : null
            ),
            'alert_preferences' => $this->when($estProprietaire, $this->alert_preferences),
            'created_at'  => $this->created_at->toDateString(),

            // Données privées visible seulement par le candidat lui-même
            'email'       => $this->when($estProprietaire, $this->user->email),
            'phone'       => $this->when($estProprietaire || $estRecruteur, $this->user->phone),
        ];
    }
}
