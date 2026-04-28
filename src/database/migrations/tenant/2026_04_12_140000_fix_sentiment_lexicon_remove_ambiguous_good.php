<?php

use App\Models\SentimentLexicon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sentiment_lexicon')) {
            return;
        }

        SentimentLexicon::withoutEvents(function (): void {
            SentimentLexicon::query()
                ->where('word', 'good')
                ->where('polarity', 'positive')
                ->delete();

            $rows = [
                ['not good', 'negative', 'en'],
                ['not great', 'negative', 'en'],
                ['come late', 'negative', 'en'],
                ['often late', 'negative', 'en'],
                ['good job', 'positive', 'en'],
                ['good teacher', 'positive', 'en'],
                ['good at teaching', 'positive', 'en'],
            ];

            foreach ($rows as [$word, $polarity, $lang]) {
                SentimentLexicon::updateOrCreate(
                    ['word' => $word],
                    [
                        'polarity' => $polarity,
                        'language' => $lang,
                        'is_active' => true,
                    ]
                );
            }
        });

        Cache::forget(SentimentLexicon::CACHE_KEY);
    }

    public function down(): void
    {
        Cache::forget(SentimentLexicon::CACHE_KEY);
    }
};
