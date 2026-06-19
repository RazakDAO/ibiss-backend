<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user()->role === 'admin') return true;

        $company   = $this->route('company');
        $recruteur = $this->user()->recruiter;
        return $company && $recruteur && $recruteur->company_id === $company->id;
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|string|max:255',
            'rccm'        => 'nullable|string|max:50',
            'sector'      => 'sometimes|string|max:100',
            'city'        => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:2000',
            'website'     => 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:255',
        ];
    }
}
