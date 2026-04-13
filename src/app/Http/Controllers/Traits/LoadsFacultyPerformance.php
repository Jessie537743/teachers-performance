<?php

namespace App\Http\Controllers\Traits;

use App\Models\DeanEvaluationFeedback;
use App\Models\EvaluationFeedback;
use App\Models\FacultyPeerEvaluationFeedback;
use App\Models\SelfEvaluationResult;
use App\Models\User;
use App\Services\EvaluationService;
use Illuminate\Support\Collection;

trait LoadsFacultyPerformance
{
    /**
     * Batch-load all faculty with their evaluation averages and computed
     * weighted results in exactly 5 queries regardless of faculty count:
     *   1. Faculty users (with facultyProfile + department eager-loaded)
     *   2. Student evaluation averages (grouped)
     *   3. Dean evaluation averages (grouped)
     *   4. Self evaluation averages (grouped)
     *   5. Peer evaluation averages (grouped)
     *
     * @param int|null    $departmentId  Restrict to a single department.
     * @param string|null $semester      Filter evaluations to this semester.
     * @param string|null $schoolYear    Filter evaluations to this school year.
     * @param int|null    $deanUserId    When provided, restrict dean averages to
     *                                   this specific dean (used in Dean views).
     *
     * @return Collection<int, array{
     *   user: User,
     *   profile: \App\Models\FacultyProfile,
     *   department: string,
     *   department_code: string,
     *   student_avg: float|null,
     *   dean_avg: float|null,
     *   self_avg: float|null,
     *   peer_avg: float|null,
     *   weighted_average: float,
     *   performance_level: string,
     *   badge_class: string,
     *   components: array,
     * }>
     */
    protected function loadFacultyWithPerformance(
        ?int $departmentId = null,
        ?string $semester = null,
        ?string $schoolYear = null,
        ?int $deanUserId = null
    ): Collection {
        // Query 1 — load all faculty with relationships already eager-loaded
        $faculty = User::with(['facultyProfile.department', 'department'])
            ->whereHasRole('faculty')
            ->where('is_active', true)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->get();

        $profileIds = $faculty->pluck('facultyProfile.id')->filter()->values();

        if ($profileIds->isEmpty()) {
            return collect();
        }

        // Query 2 — student evaluation averages, one row per faculty_id
        $studentAvgs = EvaluationFeedback::whereIn('faculty_id', $profileIds)
            ->when($semester,   fn($q) => $q->where('semester', $semester))
            ->when($schoolYear, fn($q) => $q->where('school_year', $schoolYear))
            ->selectRaw('faculty_id, AVG(total_average) as avg_score')
            ->groupBy('faculty_id')
            ->pluck('avg_score', 'faculty_id');

        // Query 3 — dean evaluation averages, optionally scoped to one dean
        $deanAvgs = DeanEvaluationFeedback::whereIn('faculty_id', $profileIds)
            ->when($semester,    fn($q) => $q->where('semester', $semester))
            ->when($schoolYear,  fn($q) => $q->where('school_year', $schoolYear))
            ->when($deanUserId,  fn($q) => $q->where('dean_user_id', $deanUserId))
            ->selectRaw('faculty_id, AVG(total_average) as avg_score')
            ->groupBy('faculty_id')
            ->pluck('avg_score', 'faculty_id');

        // Query 4 — self evaluation averages
        $selfAvgs = SelfEvaluationResult::whereIn('faculty_id', $profileIds)
            ->when($semester,   fn($q) => $q->where('semester', $semester))
            ->when($schoolYear, fn($q) => $q->where('school_year', $schoolYear))
            ->selectRaw('faculty_id, AVG(total_average) as avg_score')
            ->groupBy('faculty_id')
            ->pluck('avg_score', 'faculty_id');

        // Query 5 — peer evaluation averages (as evaluatee)
        $peerAvgs = FacultyPeerEvaluationFeedback::whereIn('evaluatee_faculty_id', $profileIds)
            ->where('evaluation_type', 'peer')
            ->when($semester,   fn($q) => $q->where('semester', $semester))
            ->when($schoolYear, fn($q) => $q->where('school_year', $schoolYear))
            ->selectRaw('evaluatee_faculty_id as faculty_id, AVG(total_average) as avg_score')
            ->groupBy('evaluatee_faculty_id')
            ->pluck('avg_score', 'faculty_id');

        // Map in PHP — zero additional queries
        return $faculty
            ->filter(fn(User $user) => $user->facultyProfile !== null)
            ->map(function (User $user) use ($studentAvgs, $deanAvgs, $selfAvgs, $peerAvgs) {
                $profileId = $user->facultyProfile->id;

                $studentAvg = isset($studentAvgs[$profileId]) ? (float) $studentAvgs[$profileId] : null;
                $deanAvg    = isset($deanAvgs[$profileId])    ? (float) $deanAvgs[$profileId]    : null;
                $selfAvg    = isset($selfAvgs[$profileId])    ? (float) $selfAvgs[$profileId]    : null;
                $peerAvg    = isset($peerAvgs[$profileId])    ? (float) $peerAvgs[$profileId]    : null;

                $personnelType = $user->facultyProfile->evaluationCriteriaPersonnelType();

                $weighted = EvaluationService::computeWeightedResult(
                    $studentAvg,
                    $deanAvg,
                    $selfAvg,
                    $peerAvg,
                    $personnelType
                );

                return [
                    'user'              => $user,
                    'profile'           => $user->facultyProfile,
                    'department'        => $user->department?->name ?? '—',
                    'department_code'   => $user->department?->code ?? '',
                    'student_avg'       => $studentAvg !== null ? round($studentAvg, 2) : null,
                    'dean_avg'          => $deanAvg    !== null ? round($deanAvg, 2)    : null,
                    'self_avg'          => $selfAvg    !== null ? round($selfAvg, 2)    : null,
                    'peer_avg'          => $peerAvg    !== null ? round($peerAvg, 2)    : null,
                    'weighted_average'  => $weighted['weighted_average'],
                    'performance_level' => $weighted['performance_level'],
                    'badge_class'       => EvaluationService::performanceBadgeClass($weighted['performance_level']),
                    'components'        => $weighted['components'],
                ];
            })
            ->values();
    }
}
