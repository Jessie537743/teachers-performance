<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $connection = 'central';
    protected $table = 'subscriptions';

    protected $fillable = [
        'tenant_id',
        'plan',
        'billing_cycle',
        'amount_cents',
        'currency',
        'period_start',
        'period_end',
        'status',
        'paid_at',
        'failure_reason',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'period_end'   => 'datetime',
            'paid_at'      => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount_cents / 100, 2);
    }
}
