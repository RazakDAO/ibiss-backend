<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => 'sometimes|string|max:255',
            'phone'              => 'sometimes|string|max:20|unique:users,phone,' . $this->user()->id,
            'title'              => 'sometimes|string|max:255',
            'bio'                => 'sometimes|string|max:1000',
            'city'               => 'sometimes|string|max:100',
            'visibility'         => 'sometimes|in:public,private,recruiters',
            'skills'             => 'sometimes|array',
            'skills.*'           => 'string|max:100',
            'experiences'        => 'sometimes|array',
            'experiences.*.titre'     => 'required_with:experiences|string|max:255',
            'experiences.*.entreprise' => 'required_with:experiences|string|max:255',
            'experiences.*.debut'     => 'required_with:experiences|date',
            'experiences.*.fin'       => 'nullable|date|after:experiences.*.debut',
            'education'          => 'sometimes|array',
            'education.*.diplome'    => 'required_with:education|string|max:255',
            'education.*.etablissement' => 'required_with:education|string|max:255',
            'education.*.annee'      => 'required_with:education|integer|min:1950|max:2030',
            'alert_preferences'  => 'sometimes|array',
            'alert_preferences.sectors'  => 'nullable|array',
            'alert_preferences.cities'   => 'nullable|array',
            'alert_preferences.types'    => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'visibility.in'    => 'La visibilité doit être : public, privé ou recruteurs.',
            'phone.unique'     => 'Ce numéro de téléphone est déjà utilisé.',
        ];
    }
}
