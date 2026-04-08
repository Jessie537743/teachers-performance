<?php

namespace Database\Seeders;

use App\Models\Intervention;
use Illuminate\Database\Seeder;

class InterventionSeeder extends Seeder
{
    public function run(): void
    {
        $interventions = [
            [
                'question_id'              => 1,
                'indicator'                => 'Enthusiasm in teaching',
                'meaning_low_score'        => 'Low motivation, burnout',
                'recommended_intervention' => 'Professional Motivation Workshop, Values Reorientation, Teacher Re-Engagement Training',
                'basis'                    => 'RPMS-PPST Domain 7',
            ],
            [
                'question_id'              => 2,
                'indicator'                => 'Implements school objectives',
                'meaning_low_score'        => 'Misalignment with VMGO',
                'recommended_intervention' => 'Institutional Orientation, Values Alignment Seminar',
                'basis'                    => 'CHED QA CMO 46',
            ],
            [
                'question_id'              => 3,
                'indicator'                => 'Intellectually humble & tolerant',
                'meaning_low_score'        => 'Poor interpersonal skills',
                'recommended_intervention' => 'Interpersonal & Ethical Conduct Training',
                'basis'                    => 'PPST Domain 6',
            ],
        ];

        foreach ($interventions as $intervention) {
            Intervention::create($intervention);
        }
    }
}
