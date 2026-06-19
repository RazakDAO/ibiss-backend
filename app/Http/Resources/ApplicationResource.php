<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'status'       => $this->status,
            'cover_letter' => $this->cover_letter,
            'notes'        => $this->when(
                $request->user()?->role === 'recruiter',
                $this->notes
            ),
            'tags'         => $this->tags,
            'created_at'   => $this->created_at->toDateTimeString(),
            'offre'        => $this->whenLoaded('offre', fn () => [
                'id'      => $this->offre->id,
                'title'   => $this->offre->title,
                'slug'    => $this->offre->slug,
                'city'    => $this->offre->city,
                'type'    => $this->offre->type,
                'company' => $this->offre->relationLoaded('company') && $this->offre->company ? [
                    'id'          => $this->offre->company->id,
                    'name'        => $this->offre->company->name,
                    'logo_path'   => $this->offre->company->logo_path
                        ? url(Storage::url($this->offre->company->logo_path))
                        : null,
                    'is_verified' => $this->offre->company->is_verified,
                ] : null,
            ]),
            'candidat'     => $this->whenLoaded('candidate', fn () => [
                'id'       => $this->candidate->id,
                'name'     => $this->candidate->user->name,
                'title'    => $this->candidate->title,
                'city'     => $this->candidate->city,
                'cv_path'  => $this->candidate->cv_path,
                'skills'   => $this->candidate->skills,
            ]),
        ];
    }
}
