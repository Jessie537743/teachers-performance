<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionDelegation extends Model
{
    protected $fillable = [
        'delegator_id',
        'delegatee_id',
        'permissions',
        'starts_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'starts_at'   => 'datetime',
        'expires_at'  => 'datetime',
        'revoked_at'  => 'datetime',
    ];

    public function delegator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegator_id');
    }

    public function delegatee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegatee_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query->whereNull('revoked_at')
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            });
    }

    public function isActive(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }
        $now = now();
        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->lte($now)) {
            return false;
        }
        return true;
    }
}
