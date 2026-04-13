<?php

namespace Database\Seeders;

use App\Models\SentimentLexicon;
use Illuminate\Database\Seeder;

/**
 * English + Filipino/Cebuano-style terms for comment-level sentiment (employee / evaluation comments).
 * Edit rows in `sentiment_lexicon` to tune; cache clears on save.
 */
class SentimentLexiconSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->positiveEntries() as $row) {
            SentimentLexicon::updateOrCreate(
                ['word' => $row['word']],
                [
                    'polarity' => 'positive',
                    'language' => $row['language'] ?? 'en',
                    'is_active' => true,
                ]
            );
        }

        foreach ($this->negativeEntries() as $row) {
            SentimentLexicon::updateOrCreate(
                ['word' => $row['word']],
                [
                    'polarity' => 'negative',
                    'language' => $row['language'] ?? 'en',
                    'is_active' => true,
                ]
            );
        }

        foreach ($this->neutralEntries() as $row) {
            SentimentLexicon::updateOrCreate(
                ['word' => $row['word']],
                [
                    'polarity' => 'neutral',
                    'language' => $row['language'] ?? 'en',
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @return list<array{word: string, language: string}>
     */
    private function positiveEntries(): array
    {
        $en = [
            // Common positive (English)
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
            // Extra / evaluation context (existing + phrases)
            'very good', 'good job', 'good teacher', 'good at teaching', 'good performance',
            'brilliant', 'commendable', 'best', 'commend', 'well explained', 'organized',
            'improved', 'improvement', 'well prepared', 'dedicated', 'enthusiastic', 'passionate',
            'attentive', 'punctual', 'on time', 'fair grading', 'constructive', 'encouraging',
            'thorough', 'structured', 'accessible', 'responsive', 'satisfied', 'appreciate',
            'thank you', 'thanks', 'job well done', 'keep it up', 'well done',
        ];

        $fil = [
            // Filipino / Tagalog-style
            'magaling', 'mahusay', 'napakagaling', 'napakahusay',
            'maayos', 'malinaw', 'mabait', 'maganda', 'napakaganda',
            'kaaya-aya', 'kahanga-hanga', 'mahusay magturo',
            'naiintindihan', 'madaling intindihin',
            'masipag', 'responsable', 'maaasahan',
            'magalang', 'matulungin', 'mabuting guro',
            'ang galing', 'ang husay', 'ang bait',
            'sobrang galing', 'sobrang bait',
            'napakalinaw magturo',
            // Cebuano / prior list
            'maayo', 'nindot', 'klaro', 'nasabtan', 'salamat', 'daghang salamat', 'maayong',
            'makatabang', 'masinabtanon', 'kugihan', 'maalagar', 'respito', 'makalingaw',
            'mabuti', 'magaling magturo', 'mapagmahal',
        ];

        $es = [
            'bueno', 'excelente', 'perfecto', 'maravilloso',
            'increíble', 'amable', 'profesional',
            'eficiente', 'confiable', 'claro',
        ];

        return array_merge(
            $this->mapLang(array_values(array_unique($en)), 'en'),
            $this->mapLang(array_values(array_unique($fil)), 'fil'),
            $this->mapLang(array_values(array_unique($es)), 'es')
        );
    }

    /**
     * @return list<array{word: string, language: string}>
     */
    private function negativeEntries(): array
    {
        $en = [
            'not good', 'not great', 'not helpful', 'not prepared', 'not clear', 'not professional',
            'poor', 'bad', 'worst', 'terrible', 'awful', 'horrible', 'disappointing', 'frustrating',
            'late', 'always late', 'come late', 'often late', 'unclear', 'confusing', 'rude', 'unfair', 'bias', 'biased',
            'difficult', 'hard to understand', 'needs improvement', 'need improvement',
            'unprepared', 'absent', 'unprofessional', 'disorganized', 'unhelpful',
            'slow feedback', 'no feedback', 'boring', 'monotonous', 'strict', 'intimidating',
            'ignored', 'dismissive', 'inconsistent', 'messy', 'rushed', 'careless', 'problematic',
            'unsatisfactory', 'substandard', 'failed', 'cannot understand', 'too fast', 'too slow',
        ];

        $filCeb = [
            'kulang', 'lisod', 'dili klaro', 'dili maayo', 'hinay', 'delay', 'sayop',
            'hindi maganda', 'mahirap intindihin', 'pangit', 'bastos', 'wala sa oras',
            'hindi prepared', 'walang kwenta', 'confusing kaayo',
        ];

        return array_merge(
            $this->mapLang($en, 'en'),
            $this->mapLang($filCeb, 'mixed')
        );
    }

    /**
     * @return list<array{word: string, language: string}>
     */
    private function neutralEntries(): array
    {
        $en = [
            'okay', 'ok', 'average', 'mediocre', 'mixed', 'so-so', 'so so', 'neither', 'fine',
            'adequate', 'passable', 'moderate', 'normal', 'standard', 'typical', 'regular',
            'acceptable', 'alright', 'all right', 'not bad', 'could be better', 'as expected',
            'somewhat', 'partially', 'in between',
        ];

        $filCeb = [
            'medyo', 'sakto lang', 'pasado lang', 'average ra', 'wa koy ikasulti', 'okay ra',
        ];

        return array_merge(
            $this->mapLang($en, 'en'),
            $this->mapLang($filCeb, 'mixed')
        );
    }

    /**
     * @param  list<string>  $words
     * @return list<array{word: string, language: string}>
     */
    private function mapLang(array $words, string $lang): array
    {
        $out = [];
        foreach ($words as $w) {
            $out[] = [
                'word' => mb_strtolower(trim($w)),
                'language' => $lang,
            ];
        }

        return $out;
    }
}
