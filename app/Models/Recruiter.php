<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recruiter extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'plan',
        'plan_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'plan_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function offres(): HasMany
    {
        return $this->hasMany(Offre::class);
    }

    public function aPlanActif(): bool
    {
        if ($this->plan === 'free') {
            return true;
        }

        return $this->plan_expires_at && $this->plan_expires_at->isFuture();
    }
}
