<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'job_title',
        'department',
        'work_location',
        'contract_type',
        'contract_status',
        'access_status',
        'hired_at',
        'end_date',
        'avatar_path',
        'meta',
        'salaire_base',
        'cnss_numero',
        'iuts_numero',
    ];

    protected $casts = [
        'hired_at'  => 'date',
        'end_date'  => 'date',
        'meta'      => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getNomCompletAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getInitialesAttribute(): string
    {
        return strtoupper(
            substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1)
        );
    }
}
