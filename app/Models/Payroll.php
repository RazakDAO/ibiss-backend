<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id', 'company_id', 'genere_par',
        'mois', 'annee', 'salaire_brut',
        'cnss_patronal', 'cnss_salarial', 'iuts',
        'heures_supp_montant', 'avances_deduites', 'net_a_payer',
        'statut', 'date_paiement',
    ];

    protected $casts = [
        'date_paiement' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function generateurPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'genere_par');
    }
}
