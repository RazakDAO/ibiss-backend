<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitierPaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => 'required|in:orange_money,moov_money',
            'type'     => 'required|in:plan,sponsored,urgent,pack',
            'plan'     => 'required_if:type,plan|in:starter,pro,rh,enterprise',
            'offre_id' => 'required_if:type,sponsored|exists:offres,id',
            'phone'    => 'required|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'provider.in'       => 'Le fournisseur doit être orange_money ou moov_money.',
            'type.in'           => 'Type de paiement invalide.',
            'plan.required_if'  => 'Veuillez sélectionner un plan.',
            'plan.in'           => 'Plan invalide.',
            'offre_id.required_if' => 'L\'offre est requise pour un sponsoring.',
            'phone.required'    => 'Le numéro de téléphone est obligatoire.',
        ];
    }
}
