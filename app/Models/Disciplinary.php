<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disciplinary extends Model
{
    protected $fillable = [
        'employee_id', 'company_id',
        'type', 'gravite', 'date_sanction',
        'motif', 'consequences', 'statut',
    ];

    protected $casts = [
        'date_sanction' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
