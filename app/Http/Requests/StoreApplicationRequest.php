<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cover_letter' => 'nullable|string|max:3000',
        ];
    }

    public function messages(): array
    {
        return [
            'cover_letter.max' => 'La lettre de motivation ne peut pas dépasser 3000 caractères.',
        ];
    }
}
