<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackAiSuggestion extends Model
{
    protected $fillable = [
        'comment_hash',
        'variant_seed',
        'polarity',
        'source_kind',
        'summary',
        'suggested_actions',
        'root_cause',
        'themes',
        'engine',
    ];

    protected function casts(): array
    {
        return [
            'suggested_actions' => 'array',
            'themes'            => 'array',
        ];
    }
}
