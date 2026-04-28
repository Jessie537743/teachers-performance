<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'criteria_id',
        'question_text',
        'response_type',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'float',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criteria_id');
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }
}
