<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class RecruiterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->user->name,
            'email'           => $this->user->email,
            'phone'           => $this->user->phone,
            'plan'            => $this->plan,
            'plan_expires_at' => $this->plan_expires_at?->toDateString(),
            'plan_actif'      => $this->aPlanActif(),
            'company'         => $this->whenLoaded('company', fn () => $this->company ? [
                'id'          => $this->company->id,
                'name'        => $this->company->name,
                'slug'        => $this->company->slug,
                'rccm'        => $this->company->rccm,
                'sector'      => $this->company->sector,
                'city'        => $this->company->city,
                'description' => $this->company->description,
                'website'     => $this->company->website,
                'phone'       => $this->company->phone,
                'email'       => $this->company->email,
                'logo_path'   => $this->company->logo_path
                    ? url(Storage::url($this->company->logo_path))
                    : null,
                'is_verified' => $this->company->is_verified,
            ] : null),
            'created_at'      => $this->created_at->toDateString(),
        ];
    }
}
