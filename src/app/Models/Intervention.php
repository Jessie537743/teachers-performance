<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Intervention extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'question_id',
        'indicator',
        'meaning_low_score',
        'recommended_intervention',
        'basis',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
