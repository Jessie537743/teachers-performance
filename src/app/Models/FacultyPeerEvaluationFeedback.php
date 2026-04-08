<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacultyPeerEvaluationFeedback extends Model
{
    protected $table = 'faculty_peer_evaluation_feedback';

    public $timestamps = false;

    protected $fillable = [
        'evaluator_faculty_id',
        'evaluatee_faculty_id',
        'evaluation_type',
        'semester',
        'school_year',
        'comment',
        'recommendation',
        'total_average',
        'performance_level',
        'weighted_percentage',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'total_average'       => 'decimal:2',
            'weighted_percentage' => 'decimal:2',
            'created_at'          => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(FacultyProfile::class, 'evaluator_faculty_id');
    }

    public function evaluatee(): BelongsTo
    {
        return $this->belongsTo(FacultyProfile::class, 'evaluatee_faculty_id');
    }
}
