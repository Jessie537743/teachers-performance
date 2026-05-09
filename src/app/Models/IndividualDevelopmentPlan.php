<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndividualDevelopmentPlan extends Model
{
    protected $fillable = [
        'faculty_id',
        'school_year',
        'semester',
        'summary',
        'strengths',
        'growth_areas',
        'goals',
        'action_items',
        'expected_outcomes',
        'recommended_resources',
        'generated_from',
        'engine',
        'model_version',
        'status',
        'generated_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'strengths'             => 'array',
            'growth_areas'          => 'array',
            'goals'                 => 'array',
            'action_items'          => 'array',
            'expected_outcomes'     => 'array',
            'recommended_resources' => 'array',
            'generated_from'        => 'array',
            'completed_at'          => 'datetime',
        ];
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(FacultyProfile::class, 'faculty_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function scopeForPeriod(Builder $q, string $schoolYear, string $semester): Builder
    {
        return $q->where('school_year', $schoolYear)->where('semester', $semester);
    }

    public function scopeActiveOrDraft(Builder $q): Builder
    {
        return $q->whereIn('status', ['draft', 'active']);
    }
}
