<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'rccm'        => 'nullable|string|max:100|unique:companies,rccm',
            'sector'      => 'nullable|string|max:100',
            'city'        => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'website'     => 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de l\'entreprise est obligatoire.',
            'rccm.unique'   => 'Ce numéro RCCM est déjà enregistré.',
            'website.url'   => 'L\'adresse du site web est invalide.',
        ];
    }
}
