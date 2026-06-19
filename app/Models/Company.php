<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'rccm',
        'sector',
        'city',
        'logo_path',
        'description',
        'website',
        'phone',
        'email',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name) . '-' . Str::random(4);
            }
        });
    }

    public function recruiters(): HasMany
    {
        return $this->hasMany(Recruiter::class);
    }

    public function offres(): HasMany
    {
        return $this->hasMany(Offre::class);
    }
}
