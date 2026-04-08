<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeerEvaluationResult extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'faculty_id',
        'evaluator_faculty_id',
        'department_id',
        'semester',
        'school_year',
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
}
