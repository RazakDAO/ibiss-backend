<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class JobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'slug'          => $this->slug,
            'description'   => $this->description,
            'sector'        => $this->sector,
            'city'          => $this->city,
            'type'          => $this->type,
            'level'         => $this->level,
            'salary_range'  => $this->salary_range,
            'status'        => $this->status,
            'is_sponsored'  => $this->is_sponsored,
            'expires_at'    => $this->expires_at?->toDateString(),
            'created_at'    => $this->created_at->toDateTimeString(),
            'company'       => $this->whenLoaded('company', fn () => [
                'id'          => $this->company->id,
                'name'        => $this->company->name,
                'slug'        => $this->company->slug,
                'logo_path'   => $this->company->logo_path
                    ? url(Storage::url($this->company->logo_path))
                    : null,
                'city'        => $this->company->city,
                'is_verified' => $this->company->is_verified,
                'email'       => $this->company->email,
                'phone'       => $this->company->phone,
                'website'     => $this->company->website,
            ]),
            'candidatures_count' => $this->whenCounted('applications'),
        ];
    }
}
