<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriterionEvaluatorGroup extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'criterion_id',
        'evaluator_group',
        'weight',
    ];

    protected $casts = [
        'weight' => 'float',
    ];

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criterion_id');
    }
}
