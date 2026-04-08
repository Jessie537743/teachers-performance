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
    ];

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criterion_id');
    }
}
