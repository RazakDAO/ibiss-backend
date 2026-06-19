<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalVisit extends Model
{
    protected $fillable = [
        'employee_id', 'company_id',
        'date_visite', 'date_prochaine_visite',
        'medecin', 'lieu', 'resultat', 'observations',
    ];

    protected $casts = [
        'date_visite'           => 'date',
        'date_prochaine_visite' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
