<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiFeatureImportance extends Model
{
    protected $table = 'ai_feature_importance';

    public $timestamps = false;

    protected $fillable = [
        'model_name',
        'feature_name',
        'importance_score',
        'semester',
        'school_year',
        'recorded_date',
    ];

    protected function casts(): array
    {
        return [
            'importance_score' => 'decimal:6',
            'recorded_date'    => 'datetime',
        ];
    }
}
