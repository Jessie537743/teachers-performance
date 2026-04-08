<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultyPrediction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'faculty_id',
        'department_id',
        'semester',
        'school_year',
        'avg_score',
        'response_count',
        'predicted_performance',
        'recommendation',
        'model_used',
        'prediction_date',
    ];

    protected function casts(): array
    {
        return [
            'avg_score'       => 'decimal:2',
            'prediction_date' => 'datetime',
        ];
    }
}
