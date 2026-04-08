<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultyEvaluationSummary extends Model
{
    protected $table = 'faculty_evaluation_summary';

    public $timestamps = false;

    protected $fillable = [
        'faculty_id',
        'department_id',
        'semester',
        'school_year',
        'avg_score',
        'total_responses',
        'previous_score',
        'improvement_rate',
        'evaluation_date',
    ];

    protected function casts(): array
    {
        return [
            'avg_score'        => 'decimal:2',
            'previous_score'   => 'decimal:2',
            'improvement_rate' => 'decimal:2',
            'evaluation_date'  => 'datetime',
        ];
    }
}
