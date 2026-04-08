<?php

namespace Database\Seeders;

use App\Models\Criterion;
use App\Models\Question;
use Illuminate\Database\Seeder;

/**
 * Dean/Head personnel (Role/Position in Faculty Management) load criteria tagged
 * dean_head_teaching / dean_head_non_teaching. This seeder defines the standard
 * academic-administrator rubric (aligned with Dean/Head tab in Evaluation Criteria).
 */
class AcademicAdministratorsCriteriaSeeder extends Seeder
{
    private const EVALUATOR_GROUPS = ['student', 'peer', 'self', 'dean'];

    private const DEAN_HEAD_PERSONNEL = ['dean_head_teaching', 'dean_head_non_teaching'];

    public function run(): void
    {
        $this->pruneLegacyPlaceholderCriteria();
        $this->consolidateLegacyAdministrativeCriterionFromCriteriaSeeder();
        $this->prefixInstructionalAndPersonnelCriterionTitles();

        $sections = [
            'A. ADMINISTRATIVE/SUPERVISORY COMPETENCE' => [
                'Is familiar with the various phases of his/her work.',
                'Demonstrates leadership, especially by providing clear and consistent directions and anticipating problems with advance planning.',
                'Spends a reasonable time for classroom observation regularly.',
                'Encourages teachers to share ideas & suggestions when planning school programs.',
                'Encourages teachers and administrators to work as a team.',
                'Has supervisory programs that develop and utilize the talents of Department or Unit Heads and teachers.',
                'Evaluates teachers only after sufficient observation.',
                'Focuses on teacher\'s performance, not personality, when evaluating teachers.',
                'Respects teachers\' rights, especially as provided by law and contract.',
                'Knows how to confer timely compliments as well as administer necessary reprimands.',
                'Has the ability and courage to give constructive criticism in a friendly, firm and positive manner.',
                'Delegates leadership responsibilities among teachers and staff whenever appropriate.',
                'Keeps school personnel informed on policies, rules and regulations.',
                'Organizes his/her office and records so that they are an example of efficiency and effectiveness.',
                'Keeps the office open to communication from all sources.',
            ],
            'B. INSTRUCTIONAL LEADERSHIP' => [
                'Provides clear direction for instruction',
                'Ensures curriculum alignment',
                'Supports innovative teaching methodologies',
                'Monitors instructional quality',
                'Provides constructive feedback to faculty',
                'Promotes use of technology in teaching',
                'Supports assessment and evaluation practices',
                'Encourages research and scholarly activities',
                'Facilitates professional learning communities',
                'Promotes student-centered learning',
                'Ensures adequate learning resources',
                'Supports faculty in curriculum development',
                'Monitors student academic performance',
                'Promotes inclusive education practices',
                'Supports community engagement activities',
                'Ensures quality assurance in instruction',
                'Facilitates accreditation compliance',
                'Promotes interdisciplinary collaboration',
                'Supports mentoring programs',
            ],
            'C. PERSONAL/ PROFESSIONAL RELATIONSHIPS WITH PERSONNEL' => [
                'Maintains professional relationships with faculty',
                'Communicates respectfully with personnel',
                'Values diversity and inclusiveness',
                'Supports team building activities',
                'Handles personnel concerns fairly',
                'Maintains confidentiality of personnel matters',
                'Recognizes personnel achievements',
                'Promotes positive work environment',
                'Supports personnel welfare programs',
                'Facilitates open communication channels',
                'Mediates conflicts professionally',
                'Promotes collaborative decision making',
            ],
            'D. STAKEHOLDER ENGAGEMENT AND INSTITUTIONAL REPRESENTATION' => [
                'Builds and sustains constructive relationships with students, parents, and alumni.',
                'Maintains effective partnerships with industry, government, and community organizations.',
                'Represents the college or unit accurately and professionally in official meetings and events.',
                'Promotes institutional policies and priorities clearly to internal and external audiences.',
                'Supports accreditation, audit, and quality-assurance activities with timely documentation.',
                'Ensures complaints and public concerns are addressed through appropriate channels.',
                'Encourages faculty and staff participation in outreach and extension activities.',
                'Aligns unit initiatives with the institution\'s vision, mission, and development goals.',
                'Upholds ethical standards and transparency in dealings with external stakeholders.',
                'Facilitates resource mobilization and collaboration for program improvement.',
                'Monitors stakeholder feedback to improve services and academic programs.',
                'Demonstrates accountability in reporting and communicating institutional performance.',
            ],
        ];

        foreach ($sections as $criterionName => $questionTexts) {
            $criterion = Criterion::firstOrCreate(
                ['name' => $criterionName],
                []
            );

            $this->attachDeanHeadCriterionMetadata($criterion);

            if ($criterion->questions()->count() === 0) {
                foreach ($questionTexts as $text) {
                    Question::create([
                        'criteria_id' => $criterion->id,
                        'question_text' => $text,
                    ]);
                }
            }
        }

        $this->ensureDeanHeadPivotsForExistingAdministratorCriteria();
    }

    /**
     * CriteriaSeeder creates "ADMINISTRATIVE/SUPERVISORY COMPETENCE" with placeholder questions.
     * Rename to match the Dean/Head admin rubric title and replace placeholder items once.
     */
    private function consolidateLegacyAdministrativeCriterionFromCriteriaSeeder(): void
    {
        $legacy = Criterion::query()
            ->where('name', 'ADMINISTRATIVE/SUPERVISORY COMPETENCE')
            ->first();

        if (! $legacy) {
            return;
        }

        $legacy->update(['name' => 'A. ADMINISTRATIVE/SUPERVISORY COMPETENCE']);

        $firstText = $legacy->questions()->orderBy('id')->value('question_text');
        if ($firstText === 'Plans activities and budget') {
            $legacy->questions()->delete();
        }
    }

    private function prefixInstructionalAndPersonnelCriterionTitles(): void
    {
        $instructional = Criterion::query()
            ->where('name', 'INSTRUCTIONAL LEADERSHIP')
            ->first();
        if ($instructional) {
            $instructional->update(['name' => 'B. INSTRUCTIONAL LEADERSHIP']);
        }

        $personnel = Criterion::query()
            ->where('name', 'PERSONAL/ PROFESSIONAL RELATIONSHIPS WITH PERSONNEL')
            ->first();
        if ($personnel) {
            $personnel->update(['name' => 'C. PERSONAL/ PROFESSIONAL RELATIONSHIPS WITH PERSONNEL']);
        }
    }

    private function pruneLegacyPlaceholderCriteria(): void
    {
        $legacy = Criterion::query()
            ->where('name', 'like', 'EVALUATION FOR ACADEMIC ADMINISTRATORS%')
            ->get();

        foreach ($legacy as $criterion) {
            $criterion->questions()->delete();
            $criterion->evaluatorGroups()->delete();
            $criterion->personnelTypes()->delete();
            $criterion->delete();
        }
    }

    private function attachDeanHeadCriterionMetadata(Criterion $criterion): void
    {
        foreach (self::EVALUATOR_GROUPS as $group) {
            $criterion->evaluatorGroups()->firstOrCreate(
                ['evaluator_group' => $group],
                []
            );
        }

        foreach (self::DEAN_HEAD_PERSONNEL as $personnelType) {
            $criterion->personnelTypes()->firstOrCreate(
                ['personnel_type' => $personnelType],
                []
            );
        }
    }

    /**
     * If criteria were created earlier (e.g. CriteriaSeeder) under slightly different names,
     * still attach Dean/Head evaluatee pivots so Faculty Dean/Head rows match the admin rubric.
     */
    private function ensureDeanHeadPivotsForExistingAdministratorCriteria(): void
    {
        $patterns = [
            '%ADMINISTRATIVE/SUPERVISORY COMPETENCE%',
            '%INSTRUCTIONAL LEADERSHIP%',
            '%PERSONAL/%RELATIONSHIPS WITH PERSONNEL%',
            '%STAKEHOLDER ENGAGEMENT%',
        ];

        foreach ($patterns as $like) {
            foreach (Criterion::query()->where('name', 'like', $like)->get() as $criterion) {
                $this->attachDeanHeadCriterionMetadata($criterion);
            }
        }
    }

    /**
     * Guarantees A.–D. rubric rows always carry student/peer/self/dean + Dean/Head personnel pivots
     * (fixes databases where only part of the administrator set was linked).
     */
    private function ensurePrefixedAdministratorLikertPivots(): void
    {
        foreach (Criterion::query()
            ->where(function ($q) {
                $q->where('name', 'like', 'A.%')
                    ->orWhere('name', 'like', 'B.%')
                    ->orWhere('name', 'like', 'C.%')
                    ->orWhere('name', 'like', 'D.%');
            })
            ->get() as $criterion) {
            $this->attachDeanHeadCriterionMetadata($criterion);
        }
    }
}
