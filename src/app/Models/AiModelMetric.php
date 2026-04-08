<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModelMetric extends Model
{
    protected $table = 'ai_model_metrics';

    public $timestamps = false;

    protected $fillable = [
        'model_name',
        'semester',
        'school_year',
        'accuracy',
        'precision_score',
        'recall_score',
        'f1_score',
        'training_samples',
        'testing_samples',
        'model_version',
        'training_date',
    ];

    protected function casts(): array
    {
        return [
            'accuracy'        => 'decimal:4',
            'precision_score' => 'decimal:4',
            'recall_score'    => 'decimal:4',
            'f1_score'        => 'decimal:4',
            'training_date'   => 'datetime',
        ];
    }
}
