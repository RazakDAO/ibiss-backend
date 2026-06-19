<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Offre extends Model
{
    use SoftDeletes;

    protected $table = 'offres';

    protected $fillable = [
        'company_id',
        'recruiter_id',
        'title',
        'slug',
        'description',
        'sector',
        'city',
        'type',
        'level',
        'salary_range',
        'status',
        'is_sponsored',
        'sponsored_until',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_sponsored'    => 'boolean',
            'sponsored_until' => 'datetime',
            'expires_at'      => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $offre) {
            if (empty($offre->slug)) {
                $offre->slug = Str::slug($offre->title) . '-' . Str::random(6);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(Recruiter::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'offre_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where(function ($q) {
                         $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                     });
    }

    public function scopeSponsored($query)
    {
        return $query->where('is_sponsored', true)
                     ->where('sponsored_until', '>', now());
    }
}
