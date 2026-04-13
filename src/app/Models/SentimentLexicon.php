<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SentimentLexicon extends Model
{
    protected $table = 'sentiment_lexicon';

    protected $fillable = [
        'word',
        'polarity',
        'language',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public const CACHE_KEY = 'sentiment_lexicon_payload_v1';

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget(self::CACHE_KEY);
        });

        static::deleted(function (): void {
            Cache::forget(self::CACHE_KEY);
        });
    }
}
