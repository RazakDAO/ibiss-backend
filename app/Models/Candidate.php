<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'bio',
        'city',
        'visibility',
        'cv_path',
        'skills',
        'experiences',
        'education',
        'alert_preferences',
    ];

    protected function casts(): array
    {
        return [
            'skills'             => 'array',
            'experiences'        => 'array',
            'education'          => 'array',
            'alert_preferences'  => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
