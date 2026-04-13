<?php

namespace App\Services;

use App\Models\SentimentLexicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class TextSentimentAnalyzer
{
    /**
     * @return array{label: 'positive'|'negative'|'neutral', score: int}
     */
    public function analyze(string $text): array
    {
        $normalized = mb_strtolower(trim($text));
        if ($normalized === '') {
            return ['label' => 'neutral', 'score' => 0];
        }

        $lexicon = $this->lexiconPayload();

        // Mask negative phrases first (longest first) so positives like "good" cannot match inside "not good".
        [$negativeScore, $maskedForPositive] = $this->maskNegativeMatches($normalized, $lexicon['negative']);
        $positiveScore = $this->countKeywordHits($maskedForPositive, $lexicon['positive']);
        $neutralScore  = $this->countKeywordHits($normalized, $lexicon['neutral']);

        if ($positiveScore === 0 && $negativeScore === 0 && $neutralScore > 0) {
            return ['label' => 'neutral', 'score' => 0];
        }

        $score = $positiveScore - $negativeScore;

        if ($score > 0) {
            return ['label' => 'positive', 'score' => $score];
        }

        if ($score < 0) {
            return ['label' => 'negative', 'score' => $score];
        }

        return ['label' => 'neutral', 'score' => 0];
    }

    /**
     * Replace each occurrence of negative keywords (longest first) with spaces so positive
     * terms like "good" are not counted inside "not good".
     *
     * @param  list<string>  $negativeKeywords
     * @return array{0: int, 1: string}
     */
    private function maskNegativeMatches(string $normalized, array $negativeKeywords): array
    {
        $keywords = $negativeKeywords;
        usort($keywords, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        $mask = $normalized;
        $hits = 0;

        foreach ($keywords as $keyword) {
            if ($keyword === '') {
                continue;
            }
            $len = mb_strlen($keyword);
            $replacement = str_repeat(' ', $len);

            while (($p = mb_strpos($mask, $keyword)) !== false) {
                $hits++;
                $mask = mb_substr($mask, 0, $p) . $replacement . mb_substr($mask, $p + $len);
            }
        }

        return [$hits, $mask];
    }

    /**
     * Longer phrases first reduces double-counting on overlapping substrings.
     *
     * @param  list<string>  $keywords
     */
    private function countKeywordHits(string $normalized, array $keywords): int
    {
        $keywords = $keywords;
        usort($keywords, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        $hits = 0;
        foreach ($keywords as $keyword) {
            if ($keyword === '') {
                continue;
            }
            if (str_contains($normalized, $keyword)) {
                $hits++;
            }
        }

        return $hits;
    }

    /**
     * @return array{positive: list<string>, negative: list<string>, neutral: list<string>}
     */
    private function lexiconPayload(): array
    {
        return Cache::rememberForever(SentimentLexicon::CACHE_KEY, function () {
            if (! Schema::hasTable('sentiment_lexicon')) {
                return $this->fallbackLexiconPayload();
            }

            try {
                $rows = SentimentLexicon::query()
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->get(['word', 'polarity']);
            } catch (\Throwable $e) {
                return $this->fallbackLexiconPayload();
            }

            if ($rows->isEmpty()) {
                return $this->fallbackLexiconPayload();
            }

            $out = [
                'positive' => [],
                'negative' => [],
                'neutral'  => [],
            ];

            foreach ($rows as $row) {
                $w = mb_strtolower(trim((string) $row->word));
                if ($w === '') {
                    continue;
                }
                $p = (string) $row->polarity;
                if (! isset($out[$p])) {
                    continue;
                }
                $out[$p][] = $w;
            }

            foreach (array_keys($out) as $key) {
                $out[$key] = array_values(array_unique($out[$key]));
            }

            return $out;
        });
    }

    /**
     * @return array{positive: list<string>, negative: list<string>, neutral: list<string>}
     */
    private function fallbackLexiconPayload(): array
    {
        return [
            'positive' => $this->fallbackPositiveKeywords(),
            'negative' => $this->fallbackNegativeKeywords(),
            'neutral'  => $this->fallbackNeutralKeywords(),
        ];
    }

    /**
     * @return list<string>
     */
    private function fallbackPositiveKeywords(): array
    {
        return array_values(array_unique([
            // English (aligned with SentimentLexiconSeeder)
            'good', 'great', 'excellent', 'outstanding', 'amazing', 'awesome', 'nice', 'wonderful',
            'fantastic', 'superb', 'impressive', 'remarkable', 'exceptional', 'perfect',
            'efficient', 'effective', 'productive', 'reliable', 'consistent', 'accurate',
            'high-quality', 'well-done', 'excellent work', 'top-notch',
            'knowledgeable', 'well-prepared', 'competent', 'skilled', 'expert',
            'clear', 'articulate', 'explains well', 'easy to understand',
            'friendly', 'kind', 'helpful', 'respectful', 'approachable',
            'supportive', 'patient', 'caring', 'professional',
            'interactive', 'engaging', 'interesting', 'enjoyable', 'fun',
            'motivating', 'inspiring',
            'very good', 'good job', 'good teacher', 'good at', 'good at teaching', 'good performance',
            'brilliant', 'commendable', 'best', 'commend', 'well explained', 'organized',
            'improved', 'improvement', 'well prepared', 'dedicated', 'enthusiastic', 'passionate',
            'attentive', 'punctual', 'on time', 'fair grading', 'constructive', 'encouraging',
            'thorough', 'structured', 'accessible', 'responsive', 'fair', 'satisfied', 'appreciate',
            'thank you', 'thanks', 'job well done', 'keep it up', 'well done',
            // Filipino / mixed
            'magaling', 'mahusay', 'napakagaling', 'napakahusay',
            'maayos', 'malinaw', 'mabait', 'maganda', 'napakaganda',
            'kaaya-aya', 'kahanga-hanga', 'mahusay magturo',
            'naiintindihan', 'madaling intindihin',
            'masipag', 'responsable', 'maaasahan',
            'magalang', 'matulungin', 'mabuting guro',
            'ang galing', 'ang husay', 'ang bait',
            'sobrang galing', 'sobrang bait',
            'napakalinaw magturo',
            'maayo', 'nindot', 'klaro', 'nasabtan', 'salamat', 'daghang salamat', 'maayong',
            'makatabang', 'masinabtanon', 'kugihan', 'maalagar', 'respito', 'makalingaw',
            'mabuti', 'magaling magturo', 'mapagmahal',
            // Spanish
            'bueno', 'excelente', 'perfecto', 'maravilloso',
            'increíble', 'amable', 'profesional',
            'eficiente', 'confiable', 'claro',
        ]));
    }

    /**
     * @return list<string>
     */
    private function fallbackNegativeKeywords(): array
    {
        return [
            'not good',
            'not great',
            'not helpful',
            'not prepared',
            'poor',
            'bad',
            'worst',
            'late',
            'always late',
            'come late',
            'unclear',
            'confusing',
            'rude',
            'unfair',
            'bias',
            'biased',
            'difficult',
            'hard to understand',
            'needs improvement',
            'absent',
            'unprofessional',
            'slow feedback',
            'disorganized',
            'kulang',
            'lisod',
            'dili klaro',
            'dili maayo',
            'hinay',
            'delay',
        ];
    }

    /**
     * @return list<string>
     */
    private function fallbackNeutralKeywords(): array
    {
        return [
            'okay',
            'ok',
            'average',
            'mediocre',
            'mixed',
            'so-so',
            'neither',
            'fine',
            'adequate',
            'passable',
            'moderate',
            'medyo',
            'sakto lang',
        ];
    }
}
