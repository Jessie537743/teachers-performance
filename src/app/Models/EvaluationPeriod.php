<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EvaluationPeriod extends Model
{
    protected $fillable = [
        'school_year',
        'semester',
        'start_date',
        'end_date',
        'is_open',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'is_open'    => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_open', true);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_open', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }
}
