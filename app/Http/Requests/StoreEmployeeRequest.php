<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:20',
            'job_title'       => 'nullable|string|max:150',
            'department'      => 'nullable|string|max:100',
            'work_location'   => 'nullable|string|max:150',
            'contract_type'   => 'required|in:cdi,cdd,stage,freelance,partiel',
            'contract_status' => 'sometimes|in:en_cours,termine,en_attente,suspendu',
            'hired_at'        => 'required|date',
            'end_date'        => 'nullable|date|after:hired_at',
        ];
    }
}
