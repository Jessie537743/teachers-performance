<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationFeedback extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'faculty_id',
        'subject_id',
        'evaluator_type',
        'school_year',
        'semester',
        'comment',
        'sentiment_label',
        'total_average',
        'performance_level',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'total_average' => 'decimal:2',
            'created_at'    => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(FacultyProfile::class, 'faculty_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
