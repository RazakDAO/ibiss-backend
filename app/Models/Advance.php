<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Advance extends Model
{
    protected $fillable = [
        'employee_id', 'company_id',
        'montant', 'motif', 'date_demande',
        'date_remboursement_prevue', 'montant_rembourse', 'statut',
    ];

    protected $casts = [
        'date_demande'             => 'date',
        'date_remboursement_prevue' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
