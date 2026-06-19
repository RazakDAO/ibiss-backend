<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    protected $fillable = [
        'employee_id', 'company_id', 'semaine_debut', 'horaires',
    ];

    protected $casts = [
        'semaine_debut' => 'date',
        'horaires'      => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
