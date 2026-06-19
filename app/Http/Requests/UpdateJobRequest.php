<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        $offre = $this->route('job');
        return $offre && $offre->recruiter_id === $this->user()->recruiter?->id;
    }

    public function rules(): array
    {
        return [
            'title'        => 'sometimes|string|max:255',
            'description'  => 'sometimes|string|min:50',
            'sector'       => 'sometimes|string|max:100',
            'city'         => 'sometimes|string|max:100',
            'type'         => 'sometimes|in:cdi,cdd,stage,freelance,partiel',
            'level'        => 'sometimes|in:sans_experience,junior,confirme,senior',
            'salary_range' => 'nullable|string|max:100',
            'expires_at'   => 'nullable|date|after:today',
            'status'       => 'sometimes|in:draft,pending,active,expired,archived',
        ];
    }
}
