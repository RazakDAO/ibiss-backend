<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Internship extends Model
{
    protected $fillable = [
        'company_id', 'tuteur_id',
        'nom_complet', 'email', 'phone',
        'ecole', 'type', 'poste', 'departement',
        'date_debut', 'date_fin', 'gratification',
        'statut', 'objectifs',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
    ];

    public function tuteur(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'tuteur_id');
    }
}
