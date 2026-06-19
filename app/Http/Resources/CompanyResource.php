<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'slug'         => $this->slug,
            'sector'       => $this->sector,
            'city'         => $this->city,
            'description'  => $this->description,
            'website'      => $this->website,
            'phone'        => $this->phone,
            'email'        => $this->email,
            'rccm'         => $this->rccm,
            'logo_url'     => $this->logo_path ? Storage::url($this->logo_path) : null,
            'is_verified'  => $this->is_verified,
            'offres_count' => $this->whenCounted('offres'),
            'created_at'   => $this->created_at->toDateString(),
        ];
    }
}
