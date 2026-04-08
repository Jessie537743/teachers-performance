<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriterionPersonnelType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'criterion_id',
        'personnel_type',
    ];

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criterion_id');
    }
}
