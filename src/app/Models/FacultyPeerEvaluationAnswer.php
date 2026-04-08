<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacultyPeerEvaluationAnswer extends Model
{
    protected $table = 'faculty_peer_evaluation_answers';

    public $timestamps = false;

    protected $fillable = [
        'evaluator_faculty_id',
        'evaluatee_faculty_id',
        'evaluation_type',
        'criteria_id',
        'question_id',
        'rating',
        'semester',
        'school_year',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'rating'     => 'integer',
            'created_at' => 'datetime',
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

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criteria_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
