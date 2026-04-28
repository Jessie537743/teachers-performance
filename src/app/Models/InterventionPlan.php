<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterventionPlan extends Model
{
    protected $fillable = [
        'faculty_id',
        'school_year',
        'semester',
        'severity',
        'summary',
        'action_items',
        'expected_outcome',
        'signal_clusters',
        'model_version',
        'status',
        'created_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'action_items'     => 'array',
            'expected_outcome' => 'array',
            'signal_clusters'  => 'array',
            'completed_at'     => 'datetime',
        ];
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(FacultyProfile::class, 'faculty_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
