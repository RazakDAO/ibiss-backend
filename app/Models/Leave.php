<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    protected $fillable = [
        'employee_id', 'company_id', 'approbateur_id',
        'type', 'date_debut', 'date_fin', 'nb_jours',
        'motif', 'statut', 'motif_rejet',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approbateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approbateur_id');
    }
}
