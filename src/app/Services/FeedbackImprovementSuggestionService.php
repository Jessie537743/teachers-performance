<?php

namespace App\Services;

use App\Models\FeedbackAiSuggestion;
use App\Models\Intervention;
use Illuminate\Support\Str;

/**
 * AI-powered improvement suggestions for a single free-text feedback comment.
 *
 * Free-tier engine: deterministic local NLP. No external API call. The output
 * matches the shape your reference design used (Sentiment Summary, Suggested
 * Actions, Root Cause), and is cached per comment so repeated views are
 * instant.
 *
 * Pipeline:
 *   1. TextSentimentAnalyzer  → polarity (positive/negative/neutral)
 *   2. Theme detection        → keyword match against curriculum themes
 *   3. Intervention library   → pulls curated actions for matching themes
 *   4. Templated narrative    → variant_seed lets "Regenerate" produce a
 *                               different phrasing while staying grounded.
 *
 * Replace `engine='local-nlp-v1'` with a real LLM later by swapping
 * generateFresh()'s body for an API call returning the same JSON shape.
 */
class FeedbackImprovementSuggestionService
{
    public const ENGINE = 'local-nlp-v1';

    public function __construct(
        private readonly TextSentimentAnalyzer $sentiment,
    ) {}

    /**
     * Get a suggestion for a comment — cached lookup unless $forceRegenerate.
     *
     * @return array{
     *   id: int|null,
     *   polarity: string,
     *   summary: string,
     *   suggested_actions: array<int, string>,
     *   root_cause: string,
     *   themes: array<int, string>,
     *   engine: string,
     *   variant_seed: int,
     * }
     */
    public function analyze(string $comment, string $sourceKind = 'student', bool $forceRegenerate = false): array
    {
        $normalized = $this->normalize($comment);
        if ($normalized === '') {
            return $this->emptyResponse();
        }

        $hash = hash('sha256', $normalized);

        if (! $forceRegenerate) {
            $cached = FeedbackAiSuggestion::where('comment_hash', $hash)
                ->orderByDesc('id')
                ->first();
            if ($cached) {
                return $this->present($cached);
            }
        }

        // Pick the next variant_seed for regenerate (rolls 0 → 4 → 0)
        $existingMaxSeed = FeedbackAiSuggestion::where('comment_hash', $hash)->max('variant_seed') ?? -1;
        $variant = ($existingMaxSeed + 1) % 5;

        $payload = $this->generateFresh($normalized, $sourceKind, $variant);

        $row = FeedbackAiSuggestion::create([
            'comment_hash'      => $hash,
            'variant_seed'      => $variant,
            'polarity'          => $payload['polarity'],
            'source_kind'       => $sourceKind,
            'summary'           => $payload['summary'],
            'suggested_actions' => $payload['suggested_actions'],
            'root_cause'        => $payload['root_cause'],
            'themes'            => $payload['themes'],
            'engine'            => self::ENGINE,
        ]);

        return $this->present($row);
    }

    /**
     * Detect themes + sentiment + compose the three sections.
     *
     * Variant_seed rotates phrasing slots so "Regenerate" feels alive while
     * staying grounded in the same detected themes.
     */
    private function generateFresh(string $comment, string $sourceKind, int $variant): array
    {
        $sentiment = $this->sentiment->analyze($comment);
        $polarity  = $sentiment['label'];

        $themes = $this->detectThemes($comment);
        if ($themes === []) {
            $themes = ['general'];
        }

        $summary    = $this->composeSummary($comment, $polarity, $themes, $sourceKind, $variant);
        $actions    = $this->composeActions($themes, $polarity, $variant);
        $rootCause  = $this->composeRootCause($themes, $polarity, $sourceKind, $variant);

        return [
            'polarity'          => $polarity,
            'themes'            => $themes,
            'summary'           => $summary,
            'suggested_actions' => $actions,
            'root_cause'        => $rootCause,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Theme detection — keyword match against criterion buckets
    // ─────────────────────────────────────────────────────────────────────
    private function detectThemes(string $text): array
    {
        $themes = [];
        $rules = [
            'department_service'    => ['department', 'office', 'staff', 'admin', 'service', 'support', 'response', 'helpdesk'],
            'teaching_quality'      => ['teach', 'instructor', 'lecture', 'lesson', 'explain', 'discuss', 'professor', 'classroom'],
            'subject_mastery'       => ['knowledge', 'mastery', 'expert', 'understand', 'subject', 'topic', 'content'],
            'communication'         => ['communicat', 'unclear', 'confus', 'feedback', 'reply', 'respond', 'listen'],
            'classroom_management'  => ['discipline', 'control', 'manage', 'noisy', 'distract', 'order'],
            'pacing'                => ['pace', 'rush', 'slow', 'fast', 'time', 'cover', 'finish'],
            'student_engagement'    => ['engag', 'boring', 'interest', 'fun', 'participation', 'interactive', 'activity'],
            'assessment_quality'    => ['exam', 'quiz', 'assess', 'grade', 'rubric', 'unfair', 'test', 'score'],
            'professionalism'       => ['attitude', 'respect', 'profession', 'punctual', 'late', 'rude', 'kind'],
            'facility_resources'    => ['room', 'resource', 'material', 'wifi', 'equipment', 'projector', 'lab'],
        ];

        foreach ($rules as $theme => $keywords) {
            foreach ($keywords as $kw) {
                if (Str::contains($text, $kw)) {
                    $themes[] = $theme;
                    break;
                }
            }
        }

        return array_values(array_unique($themes));
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Sentiment Summary — "what does this comment mean?"
    // ─────────────────────────────────────────────────────────────────────
    private function composeSummary(string $comment, string $polarity, array $themes, string $sourceKind, int $variant): string
    {
        $themeLabel = $this->prettyTheme($themes[0] ?? 'general');
        $reviewer   = match ($sourceKind) {
            'student' => 'students',
            'dean'    => 'the dean',
            'peer'    => 'peers',
            'self'    => 'the faculty member (self-evaluation)',
            default   => 'reviewers',
        };

        $negativeTemplates = [
            "{$reviewer} reported a poor experience related to {$themeLabel}, indicating a need for targeted improvement in this area. The concern likely stems from quality, responsiveness, or consistency of delivery.",
            "Feedback from {$reviewer} flags {$themeLabel} as a problem area. The tone suggests dissatisfaction with how this aspect is currently handled and warrants follow-up.",
            "This comment from {$reviewer} surfaces a recurring frustration around {$themeLabel}. Addressing it promptly will likely raise overall satisfaction and is a high-leverage intervention.",
            "{$reviewer} described an unsatisfactory experience tied to {$themeLabel}. The signal is clear enough to act on without waiting for additional data points.",
            "Negative feedback from {$reviewer} centred on {$themeLabel}. Pattern suggests a process or skill gap rather than a one-off incident.",
        ];

        $positiveTemplates = [
            "{$reviewer} appreciate the strength shown in {$themeLabel}, which enhances learning outcomes and engagement. This positive signal should be reinforced and shared with other faculty.",
            "Feedback from {$reviewer} highlights {$themeLabel} as a clear strength. The tone is enthusiastic — this is a behavior worth recognising and replicating across the department.",
            "Positive feedback from {$reviewer} centres on {$themeLabel}. This validates the current approach and is a candidate for case-study sharing.",
            "{$reviewer} commended this faculty's work on {$themeLabel}. Maintain the practices that drove the result.",
            "{$reviewer} expressed strong satisfaction with {$themeLabel}. Capture the underlying methods so they can be onboarded into the standard playbook.",
        ];

        $neutralTemplates = [
            "{$reviewer} described their experience with {$themeLabel} in factual terms without strong sentiment. Useful context — pair with quantitative scores to interpret.",
            "Comment from {$reviewer} on {$themeLabel} is observational rather than evaluative. Treat as informational.",
            "{$reviewer} noted aspects of {$themeLabel} without expressing satisfaction or concern. Worth tracking if similar comments appear next term.",
        ];

        $bank = match ($polarity) {
            'positive' => $positiveTemplates,
            'negative' => $negativeTemplates,
            default    => $neutralTemplates,
        };

        return $bank[$variant % count($bank)];
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Suggested Actions — concrete, checklist-style next steps
    // ─────────────────────────────────────────────────────────────────────
    private function composeActions(array $themes, string $polarity, int $variant): array
    {
        $isNegative = $polarity === 'negative';

        // Pull canned interventions from the curated library where possible.
        // (For per-comment analysis we don't have a question_id, so we fall
        // back to themed action banks — the library still informs them.)
        $libraryHints = $this->libraryHintsForThemes($themes);

        $actions = [];

        foreach ($themes as $theme) {
            $bank = $isNegative
                ? $this->negativeActionsByTheme()[$theme] ?? $this->negativeActionsByTheme()['general']
                : $this->positiveActionsByTheme()[$theme] ?? $this->positiveActionsByTheme()['general'];

            // Variant rotation across the bank — same theme regenerates with
            // different phrasing each time the user clicks Regenerate.
            $count = count($bank);
            for ($i = 0; $i < min(3, $count); $i++) {
                $idx = ($variant + $i) % $count;
                $actions[] = $bank[$idx];
            }
        }

        // Inject a library-backed action if any matched
        if ($libraryHints) {
            $actions[] = "Apply the institution's curated intervention: " . Str::limit($libraryHints, 220, '…');
        }

        // Keep the top 5 unique to avoid wall-of-text
        $actions = array_values(array_unique($actions));
        return array_slice($actions, 0, 5);
    }

    /**
     * @return array<string, list<string>>
     */
    private function negativeActionsByTheme(): array
    {
        return [
            'department_service' => [
                "Implement a structured feedback channel (monthly 5-question pulse survey) to monitor satisfaction with department services.",
                "Provide a 1-day customer-service workshop for department staff focused on response time and tone.",
                "Review wait-time logs and revise SLAs for first response (<24 hours) and resolution (<5 business days).",
                "Establish written escalation protocols and publish them on the department portal.",
                "Conduct a service-design audit with a small student panel to surface friction points.",
            ],
            'teaching_quality' => [
                "Pair the faculty with a senior mentor for a 6-week classroom-observation cycle (one observation/week + reflective notes).",
                "Enroll the faculty in a focused pedagogy refresher (20-hour micro-credential or institute course).",
                "Record one full lecture per week and schedule a 30-minute joint review with the department head.",
                "Pilot two active-learning techniques (think-pair-share, concept mapping) for 4 weeks; track exit-ticket comprehension.",
                "Add a co-teach week with a high-performing peer to model classroom delivery.",
            ],
            'subject_mastery' => [
                "Complete a subject-domain refresher course (proctored online module or graduate seminar).",
                "Deliver a 30-minute departmental brown-bag on a topic the faculty wants to deepen.",
                "Map upcoming syllabus topics to curated open-education resources before the next term.",
                "Conduct quarterly content-review sessions with the curriculum committee.",
                "Maintain a personal teaching journal capturing common student misconceptions and corrected explanations.",
            ],
            'communication' => [
                "Run weekly anonymous student feedback micro-surveys; share results with the dean within 48 hours.",
                "Adopt a fixed structure for class openings (objectives → activity → recap) to improve clarity.",
                "Provide written rubrics and assignment expectations on Day 1 of every unit.",
                "Hold scheduled office hours twice weekly with a posted sign-up sheet.",
                "Send a one-paragraph weekly summary to students recapping covered topics and next-week previews.",
            ],
            'classroom_management' => [
                "Adopt a classroom-norms agreement signed by students in week 1; reference it during disruption.",
                "Use a seating chart for the first 4 weeks to break up off-task clusters.",
                "Plan transitions explicitly — every lesson plan must specify how each segment ends.",
                "Schedule peer observation specifically focused on classroom-management techniques.",
                "Engage the guidance office for chronic disruptors using documented incident logs.",
            ],
            'pacing' => [
                "Use a fixed weekly pacing template (objectives, formative checks, summative buffer); submit weekly to coordinator.",
                "Build a 'bumper' lesson for every unit to absorb pacing slippage without losing critical content.",
                "Add 10-minute formative checks every 3 sessions to validate understanding before moving on.",
                "Audit the syllabus against actual delivery hours; flag systemic over- or under-allocation.",
                "Use exit tickets to spot when class is being rushed past the comprehension threshold.",
            ],
            'student_engagement' => [
                "Pilot two active-learning techniques (think-pair-share + concept mapping) for 4 consecutive weeks.",
                "Add one student-driven discussion segment per week (case study, problem set, or debate).",
                "Use formative polls (clickers / no-tech show-of-hands) every lesson to break up lecture monotony.",
                "Tie at least one assessment task per unit to a real-world or career-relevant scenario.",
                "Solicit student input on topic ordering for the next unit; adopt 1-2 of their suggestions.",
            ],
            'assessment_quality' => [
                "Have the department coordinator review and sign off on the next 3 summative assessments before deployment.",
                "Calibrate one rubric jointly with a peer using sample student work.",
                "Run an item-analysis after the next major exam; flag items with discrimination index <0.2.",
                "Include 1-2 application/synthesis items in every quiz to discourage rote-memorisation.",
                "Provide a one-page exam-prep guide before each summative assessment listing covered objectives.",
            ],
            'professionalism' => [
                "Schedule a structured coaching conversation with the department head; agree a 60-day standards plan.",
                "Track punctuality (start/end of class) for 4 weeks; share with the head bi-weekly.",
                "Complete the institution's professional-conduct refresher module.",
                "Pair with a faculty mentor known for collegial behaviour; meet weekly for 30 minutes.",
                "Adopt explicit communication norms with students (response window, tone) and post them.",
            ],
            'facility_resources' => [
                "File a structured ticket with the facilities team itemising resource gaps and impact on instruction.",
                "Coordinate room-allocation review with academic services before the next term.",
                "Audit AV/lab equipment quarterly; replace what's failing.",
                "Provide instructors a 'what to do when X breaks' quick-reference sheet to reduce class-time loss.",
                "Survey faculty on top 3 resource needs and share with budgeting committee.",
            ],
            'general' => [
                "Open a structured improvement-plan conversation between the faculty and the department head within 7 days.",
                "Triangulate this comment with the next batch of feedback before acting unilaterally.",
                "Document the issue and assign a follow-up review at the end of the next teaching period.",
                "Pair the faculty with a mentor for a 30-day coaching loop covering the surfaced concern.",
                "Update relevant SOPs if the same theme recurs across multiple comments this term.",
            ],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function positiveActionsByTheme(): array
    {
        return [
            'department_service' => [
                "Recognise the staff named in the feedback (private acknowledgement + small public mention).",
                "Document the workflow that drove the positive experience and add it to the department onboarding pack.",
                "Pair the named staff with a newer colleague for a 2-week shadowing period.",
                "Capture a short case-study quote for the next staff townhall.",
                "Reuse this comment as a positive baseline when reviewing service KPIs next quarter.",
            ],
            'teaching_quality' => [
                "Recognise and reward the faculty in the next departmental performance cycle.",
                "Provide a slot at the next teaching-symposium for them to present their approach.",
                "Pair them with a faculty struggling in this area for a 4-week mentor sprint.",
                "Record one of their lessons (with consent) for use in onboarding new hires.",
                "Capture the methods in a one-page SOP so the practice survives the individual.",
            ],
            'subject_mastery' => [
                "Invite the faculty to deliver a brown-bag on the topic students singled out.",
                "Co-author a unit-update memo for the curriculum committee with their content notes.",
                "Capture their topic-explanation playbook in shared department notes.",
                "Use them as a content reviewer for newer faculty's lesson plans.",
                "Recognise their mastery in the next merit cycle.",
            ],
            'communication' => [
                "Capture their classroom-communication patterns in a one-page guide for new hires.",
                "Have them run a 1-hour communication clinic for faculty who scored lower this term.",
                "Recognise the practice in the next dean's update.",
                "Record a short walkthrough of how they structure office hours and feedback.",
                "Use their template (objectives → activity → recap) as a department default.",
            ],
            'classroom_management' => [
                "Document the classroom-norms approach for inclusion in the new-faculty toolkit.",
                "Have them present techniques at the next cross-department standards meeting.",
                "Pair them with a colleague flagged for management gaps.",
                "Record one period of their class for onboarding purposes (with consent).",
                "Recognise their consistency in the merit cycle.",
            ],
            'pacing' => [
                "Adopt their pacing template as the department default starting next term.",
                "Run a 30-min workshop on their unit-by-unit time allocation.",
                "Capture the bumper-lesson trick in a shared resource folder.",
                "Pair them with faculty struggling with content coverage.",
                "Recognise it in the next teaching-excellence award.",
            ],
            'student_engagement' => [
                "Capture their engagement playbook in the new-hire onboarding pack.",
                "Have them present at the next pedagogy symposium.",
                "Pair with disengaged-feedback faculty for cross-observation.",
                "Record one engagement-heavy lesson for use as a model.",
                "Reference their methods in the next merit-cycle write-up.",
            ],
            'assessment_quality' => [
                "Adopt their rubrics as templates for the next term.",
                "Run a 1-hour assessment-design clinic featuring their items.",
                "Have them peer-review summative assessments before deployment.",
                "Capture their item-analysis approach in shared documentation.",
                "Recognise the practice in the next standards meeting.",
            ],
            'professionalism' => [
                "Recognise the faculty in the next dean's update.",
                "Pair them with new hires in the first month for cultural onboarding.",
                "Capture their communication-norm document for sharing.",
                "Use their behaviour as a positive baseline for the next standards review.",
                "Reference in the merit cycle.",
            ],
            'facility_resources' => [
                "Capture how they leverage existing resources creatively in a shared note.",
                "Have them present in the next facilities-planning session.",
                "Pair with newer faculty learning to navigate constraints.",
                "Document low-cost workarounds they've found.",
                "Recognise resourcefulness in the merit cycle.",
            ],
            'general' => [
                "Recognise the faculty in the next dean's update or department townhall.",
                "Capture the underlying behaviour in a one-page SOP for replication.",
                "Pair them with a struggling colleague for a 4-week mentor sprint.",
                "Use the comment as a benchmark when reviewing peers next term.",
                "Add to the merit-cycle evidence file.",
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Root Cause — "why is this happening / what enables this?"
    // ─────────────────────────────────────────────────────────────────────
    private function composeRootCause(array $themes, string $polarity, string $sourceKind, int $variant): string
    {
        $theme = $themes[0] ?? 'general';

        $negativeBank = [
            'department_service'    => ["Likely root cause: gaps in process documentation, undertrained staff, or unclear escalation paths within the department.", "Pattern often points to undefined SLAs and inconsistent handoffs between staff."],
            'teaching_quality'      => ["Common root cause: lack of structured peer-observation, infrequent professional development, or pacing pressure that crowds out reflection.", "Often a skill gap that wasn't surfaced because no observation cycle exists."],
            'subject_mastery'       => ["Often a gap between syllabus depth and the faculty's last refresher cycle; misconceptions go uncorrected without peer-review.", "Likely the faculty is self-teaching new units without a content reviewer."],
            'communication'         => ["Typically caused by no shared lesson structure (objectives/recap), absent feedback loops, or inconsistent office-hour availability.", "Often a clarity issue rooted in unwritten expectations rather than poor intent."],
            'classroom_management'  => ["Frequent cause: no codified classroom norms, reactive (not proactive) management, or transitions left implicit.", "Usually a planning gap — disruptions thrive where transitions are undefined."],
            'pacing'                => ["Common cause: syllabus over-allocation, lack of formative checks, or no buffer for slippage.", "Pacing gaps typically reveal an unmanaged scope-vs-time mismatch."],
            'student_engagement'    => ["Often rooted in lecture-heavy delivery without active-learning interludes, or content disconnected from learner context.", "Usually a delivery-format issue more than content quality."],
            'assessment_quality'    => ["Frequent cause: no peer review of assessments, missing rubric calibration, or item analysis never run.", "Often the assessments worked once and never got revisited as cohorts changed."],
            'professionalism'       => ["Typically reflects undocumented norms, inconsistent expectations from leadership, or missed coaching opportunities early in tenure.", "Often a culture-onboarding gap rather than character."],
            'facility_resources'    => ["Usually outside the faculty's control — points to under-investment in classroom resources, AV/equipment, or room-allocation logic.", "Often institutional rather than instructor-driven."],
            'general'               => ["Insufficient signal from a single comment to attribute root cause confidently. Triangulate with quantitative scores and other comments before acting.", "Single-comment data is informative but not diagnostic on its own."],
        ];

        $positiveBank = [
            'department_service'    => ["Likely root cause: well-documented processes, trained staff, and a culture of responsiveness in the department.", "Often signals strong leadership investment in service quality."],
            'teaching_quality'      => ["The faculty likely benefits from a strong personal preparation routine, peer mentoring, and consistent reflection on student feedback.", "Often points to ongoing self-driven professional development."],
            'subject_mastery'       => ["Mastery typically comes from continuous study, recent refreshers, and active engagement with the subject's research community.", "Suggests the faculty stays current rather than coasting on prior expertise."],
            'communication'         => ["Strong communication often reflects consistent structure, written expectations, and accessible office hours.", "Usually grounded in deliberate clarity habits."],
            'classroom_management'  => ["Usually rooted in clearly codified classroom norms and proactive transition planning.", "Reflects deliberate management routine, not personality."],
            'pacing'                => ["Likely uses a fixed pacing template and formative checks to keep the class on rhythm.", "Suggests deliberate planning, not improvisation."],
            'student_engagement'    => ["Active-learning techniques and contextually-relevant content typically drive this kind of feedback.", "Rooted in delivery-format choices rather than novelty."],
            'assessment_quality'    => ["Likely benefits from peer-reviewed assessments, calibrated rubrics, and post-exam item analysis.", "Suggests assessment design is part of the regular planning cycle."],
            'professionalism'       => ["Strong norms early in tenure, consistent leadership modeling, and active coaching usually drive this signal.", "Reflects culture-onboarding done well."],
            'facility_resources'    => ["Likely creative use of available resources combined with effective institutional support.", "Suggests resourcefulness on top of adequate infrastructure."],
            'general'               => ["Positive signal is consistent with strong fundamentals — preparation, communication, and care for student outcomes.", "Reflects the cumulative effect of multiple good habits."],
        ];

        $neutralCopy = "This comment is observational rather than evaluative. No root cause inferred; treat as situational context rather than a signal to act on.";

        if ($polarity === 'neutral') {
            return $neutralCopy;
        }

        $bank = $polarity === 'negative' ? $negativeBank : $positiveBank;
        $options = $bank[$theme] ?? $bank['general'];
        return $options[$variant % count($options)];
    }

    /**
     * Search the curated Intervention library for any text containing keywords
     * from the detected themes. Used to ground the suggestion in institution-
     * specific guidance where available.
     */
    private function libraryHintsForThemes(array $themes): ?string
    {
        if (! $themes) return null;

        $keywords = collect($themes)
            ->map(fn ($t) => str_replace('_', ' ', $t))
            ->all();

        $query = Intervention::query();
        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $kw) {
                $q->orWhere('indicator', 'like', "%{$kw}%")
                  ->orWhere('recommended_intervention', 'like', "%{$kw}%");
            }
        });

        $row = $query->first();
        return $row?->recommended_intervention ?: $row?->indicator;
    }

    private function prettyTheme(string $theme): string
    {
        return match ($theme) {
            'department_service'    => 'a specific department/service experience',
            'teaching_quality'      => 'classroom teaching and delivery',
            'subject_mastery'       => 'subject knowledge and mastery',
            'communication'         => 'communication clarity',
            'classroom_management'  => 'classroom management and discipline',
            'pacing'                => 'pacing and time management',
            'student_engagement'    => 'student engagement and interaction',
            'assessment_quality'    => 'assessment design and grading',
            'professionalism'       => 'professionalism and conduct',
            'facility_resources'    => 'facilities and learning resources',
            default                 => 'the services and learning experience',
        };
    }

    private function normalize(string $text): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $text)));
    }

    private function present(FeedbackAiSuggestion $row): array
    {
        return [
            'id'                => $row->id,
            'polarity'          => $row->polarity,
            'summary'           => $row->summary,
            'suggested_actions' => $row->suggested_actions ?? [],
            'root_cause'        => $row->root_cause ?? '',
            'themes'            => $row->themes ?? [],
            'engine'            => $row->engine,
            'variant_seed'      => $row->variant_seed,
        ];
    }

    private function emptyResponse(): array
    {
        return [
            'id'                => null,
            'polarity'          => 'neutral',
            'summary'           => 'No comment text to analyse.',
            'suggested_actions' => [],
            'root_cause'        => '',
            'themes'            => [],
            'engine'            => self::ENGINE,
            'variant_seed'      => 0,
        ];
    }
}
