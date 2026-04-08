<?php

namespace App\Services;

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

        $positiveScore = 0;
        foreach ($this->positiveKeywords() as $keyword) {
            if (str_contains($normalized, $keyword)) {
                $positiveScore++;
            }
        }

        $negativeScore = 0;
        foreach ($this->negativeKeywords() as $keyword) {
            if (str_contains($normalized, $keyword)) {
                $negativeScore++;
            }
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
     * @return list<string>
     */
    private function positiveKeywords(): array
    {
        return [
            'excellent',
            'great',
            'good',
            'very good',
            'helpful',
            'clear',
            'organized',
            'effective',
            'engaging',
            'supportive',
            'approachable',
            'improved',
            'improvement',
            'well prepared',
            'knowledgeable',
            'professional',
            'fair',
            'nice',
            'best',
            'commend',
            'outstanding',
            'maayo',
            'nindot',
            'klaro',
            'nasabtan',
            'salamat',
            'responsive',
        ];
    }

    /**
     * @return list<string>
     */
    private function negativeKeywords(): array
    {
        return [
            'poor',
            'bad',
            'worst',
            'late',
            'unclear',
            'confusing',
            'rude',
            'unfair',
            'bias',
            'biased',
            'difficult',
            'hard to understand',
            'needs improvement',
            'not prepared',
            'absent',
            'unprofessional',
            'slow feedback',
            'disorganized',
            'not helpful',
            'kulang',
            'lisod',
            'dili klaro',
            'dili maayo',
            'hinay',
            'delay',
        ];
    }
}
