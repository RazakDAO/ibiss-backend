<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    const STATUS_PENDING   = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED    = 'failed';
    const STATUS_REFUNDED  = 'refunded';

    const DUREES_PLAN = [
        'starter'    => 30,
        'pro'        => 30,
        'rh'         => 30,
        'enterprise' => 365,
    ];

    const TARIFS = [
        'plan' => [
            'starter'    => 25000,
            'pro'        => 60000,
            'rh'         => 45000,
        ],
        'sponsored' => 15000,
        'urgent'    => 10000,
        'pack'      => 200000,
    ];

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'currency',
        'provider',
        'reference',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount'   => 'integer',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estComplet(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
