<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use SoftDeletes;

    protected $table = 'offres';

    protected $fillable = [
        'company_id', 'recruiter_id', 'title', 'slug', 'description',
        'sector', 'city', 'type', 'level', 'salary_range',
        'status', 'is_sponsored', 'sponsored_until', 'expires_at',
    ];

    protected $casts = [
        'is_sponsored'    => 'boolean',
        'sponsored_until' => 'datetime',
        'expires_at'      => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function recruiter()
    {
        return $this->belongsTo(Recruiter::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'job_id');
    }
}
