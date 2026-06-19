<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'description'  => 'required|string|min:50',
            'sector'       => 'required|string|max:100',
            'city'         => 'required|string|max:100',
            'type'         => 'required|in:cdi,cdd,stage,freelance,partiel',
            'level'        => 'required|in:sans_experience,junior,confirme,senior',
            'salary_range' => 'nullable|string|max:100',
            'expires_at'   => 'nullable|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'Le titre du poste est obligatoire.',
            'description.required' => 'La description est obligatoire.',
            'description.min'      => 'La description doit contenir au moins 50 caractères.',
            'type.in'              => 'Le type de contrat est invalide.',
            'level.in'             => 'Le niveau d\'expérience est invalide.',
            'expires_at.after'     => 'La date d\'expiration doit être dans le futur.',
        ];
    }
}
