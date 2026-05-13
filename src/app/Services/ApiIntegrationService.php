<?php

namespace App\Services;

use App\Models\ApiIntegration;
use App\Models\Course;
use App\Models\Department;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Pulls Students / Departments / Subjects / Courses from a configured
 * external system and upserts them by `external_id`.
 *
 * Expected response shape per resource (the parser accepts either form):
 *
 *   { "data": [ {...}, {...} ] }
 *   [ {...}, {...} ]
 *
 * Per-row fields (all optional unless marked required):
 *
 *   departments       id*, code, name, type ("teaching" | "non-teaching")
 *   courses           id*, code, name, department_id, year_levels, semester, school_year
 *   subjects          id*, code, title, department_id, course, year_level, section, semester, school_year
 *   students          id*, email | student_id, name, department_id, course,
 *                     year_level, section, status ("regular"|"irregular"),
 *                     semester, school_year
 *
 *   * `id` is the external system's stable identifier — we store it as
 *     `external_id` on our side and use it as the upsert key.
 *
 *   `department_id` in courses/subjects/students refers to the *external*
 *   department id (i.e. the same value the departments feed uses). We resolve
 *   it locally to the matching `departments.external_id` row.
 */
class ApiIntegrationService
{
    /** Test connection by hitting the departments endpoint (lightest payload). */
    public function testConnection(ApiIntegration $integration): array
    {
        $url = $integration->urlFor('departments')
            ?: $integration->urlFor('students')
            ?: $integration->urlFor('subjects')
            ?: $integration->urlFor('courses');

        if (!$url) {
            return ['ok' => false, 'message' => 'No resource paths are configured yet.'];
        }

        try {
            $response = $this->http($integration)->get($url);
        } catch (ConnectionException $e) {
            return ['ok' => false, 'message' => 'Could not connect: ' . $e->getMessage()];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Unexpected error: ' . $e->getMessage()];
        }

        if (!$response->successful()) {
            return [
                'ok'      => false,
                'message' => 'Endpoint returned HTTP ' . $response->status() . '. Response: '
                              . str($response->body())->limit(200),
            ];
        }

        return ['ok' => true, 'message' => 'Connection succeeded (HTTP ' . $response->status() . ').'];
    }

    /**
     * Sync one resource. Returns stats: { created, updated, skipped, errors, total }.
     * Persists the same stats + status onto the integration row.
     */
    public function sync(ApiIntegration $integration, string $resource): array
    {
        if (!in_array($resource, ApiIntegration::supportedResources(), true)) {
            throw new RuntimeException("Unsupported resource: {$resource}");
        }

        $url = $integration->urlFor($resource);
        if (!$url) {
            return $this->recordResult($integration, $resource, 'error',
                "No path configured for {$resource}.", []);
        }

        try {
            $response = $this->http($integration)->get($url);
        } catch (Throwable $e) {
            return $this->recordResult($integration, $resource, 'error',
                'HTTP error: ' . $e->getMessage(), []);
        }

        if (!$response->successful()) {
            return $this->recordResult($integration, $resource, 'error',
                'Endpoint returned HTTP ' . $response->status(), []);
        }

        $rows = $this->extractRows($response->json());
        if ($rows === null) {
            return $this->recordResult($integration, $resource, 'error',
                'Unexpected response shape (expected array or { data: [...] }).', []);
        }

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0, 'total' => count($rows)];

        foreach ($rows as $row) {
            try {
                $outcome = match ($resource) {
                    'departments' => $this->upsertDepartment($row),
                    'courses'     => $this->upsertCourse($row),
                    'subjects'    => $this->upsertSubject($row),
                    'students'    => $this->upsertStudent($row),
                };
                $stats[$outcome] = ($stats[$outcome] ?? 0) + 1;
            } catch (Throwable $e) {
                $stats['errors']++;
                Log::warning('[ApiIntegration] upsert failed', [
                    'resource' => $resource,
                    'row'      => $row,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return $this->recordResult($integration, $resource, 'success', null, $stats);
    }

    // ------------------------------------------------------------------
    // Per-resource upserts
    // ------------------------------------------------------------------

    /** @return string "created" | "updated" | "skipped" */
    private function upsertDepartment(array $row): string
    {
        $extId = $this->extId($row);
        if (!$extId) return 'skipped';

        $existing = Department::where('external_id', $extId)->first();
        $payload = [
            'code'            => $row['code'] ?? ($existing?->code ?? $extId),
            'name'            => $row['name'] ?? ($existing?->name ?? 'Unnamed Department'),
            'department_type' => in_array(($row['type'] ?? null), ['teaching', 'non-teaching'], true)
                                  ? $row['type']
                                  : ($existing?->department_type ?? 'teaching'),
            'is_active'       => true,
        ];

        if ($existing) {
            $existing->fill($payload)->save();
            return 'updated';
        }

        Department::create($payload + ['external_id' => $extId]);
        return 'created';
    }

    private function upsertCourse(array $row): string
    {
        $extId = $this->extId($row);
        if (!$extId) return 'skipped';

        $deptId = $this->resolveDepartmentId($row['department_id'] ?? null);
        if (!$deptId) return 'skipped';

        $existing = Course::where('external_id', $extId)->first();
        $payload = [
            'course_code'   => $row['code'] ?? ($existing?->course_code ?? $extId),
            'course_name'   => $row['name'] ?? ($existing?->course_name ?? 'Unnamed Course'),
            'department_id' => $deptId,
            'year_levels'   => $row['year_levels'] ?? $existing?->year_levels,
            'semester'      => $row['semester']    ?? $existing?->semester,
            'school_year'   => $row['school_year'] ?? $existing?->school_year,
            'is_active'     => true,
        ];

        if ($existing) {
            $existing->fill($payload)->save();
            return 'updated';
        }

        Course::create($payload + ['external_id' => $extId]);
        return 'created';
    }

    private function upsertSubject(array $row): string
    {
        $extId = $this->extId($row);
        if (!$extId) return 'skipped';

        $deptId = $this->resolveDepartmentId($row['department_id'] ?? null);

        $existing = Subject::where('external_id', $extId)->first();
        $payload = [
            'code'          => $row['code']        ?? $existing?->code,
            'title'         => $row['title']       ?? $existing?->title,
            'department_id' => $deptId             ?? $existing?->department_id,
            'course'        => $row['course']      ?? $existing?->course,
            'year_level'    => $row['year_level']  ?? $existing?->year_level,
            'section'       => $row['section']     ?? ($existing?->section ?? '1'),
            'semester'      => $row['semester']    ?? $existing?->semester,
            'school_year'   => $row['school_year'] ?? $existing?->school_year,
        ];

        if ($existing) {
            $existing->fill($payload)->save();
            return 'updated';
        }

        Subject::create($payload + ['external_id' => $extId]);
        return 'created';
    }

    private function upsertStudent(array $row): string
    {
        $extId = $this->extId($row);
        if (!$extId) return 'skipped';

        $deptId = $this->resolveDepartmentId($row['department_id'] ?? null);
        if (!$deptId) return 'skipped';

        $email = $row['email'] ?? null;
        $name  = $row['name']  ?? null;

        $profile = StudentProfile::where('external_id', $extId)->first();

        if ($profile) {
            // Update the linked user record's profile fields. Email/name can
            // change on the external side; we trust the incoming value unless
            // it conflicts with another local user's email.
            $user = $profile->user;
            if ($user) {
                if ($email && $email !== $user->email
                    && !User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
                    $user->email = $email;
                }
                if ($name) {
                    $user->name = $name;
                }
                $user->department_id = $deptId;
                $user->save();
            }

            $profile->fill([
                'department_id' => $deptId,
                'course'        => $row['course']     ?? $profile->course,
                'year_level'    => $row['year_level'] ?? $profile->year_level,
                'section'       => $row['section']    ?? $profile->section,
                'student_status'=> in_array(($row['status'] ?? null), ['regular', 'irregular'], true)
                                    ? $row['status']
                                    : $profile->student_status,
                'semester'      => $row['semester']    ?? $profile->semester,
                'school_year'   => $row['school_year'] ?? $profile->school_year,
            ])->save();

            return 'updated';
        }

        // Create new — email is the natural join key on the User side.
        // If a user already exists with this email, link to them; otherwise
        // create a fresh user with the email-as-password convention used
        // elsewhere in the app.
        if (!$email && !$name) return 'skipped';

        $user = $email ? User::where('email', $email)->first() : null;
        if (!$user) {
            $user = User::create([
                'name'                 => $name ?? 'Imported Student',
                'email'                => $email,
                'password'             => Hash::make($email ?? str()->random(16)),
                'roles'                => ['student'],
                'is_active'            => true,
                'department_id'        => $deptId,
                'must_change_password' => true,
            ]);
        }

        StudentProfile::create([
            'external_id'   => $extId,
            'user_id'       => $user->id,
            'department_id' => $deptId,
            'course'        => $row['course']     ?? '',
            'year_level'    => $row['year_level'] ?? '1',
            'section'       => $row['section']    ?? '1',
            'student_status'=> in_array(($row['status'] ?? null), ['regular', 'irregular'], true)
                                ? $row['status']
                                : 'regular',
            'semester'      => $row['semester']    ?? '',
            'school_year'   => $row['school_year'] ?? '',
        ]);

        return 'created';
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function http(ApiIntegration $integration)
    {
        $headers = array_merge(
            $integration->buildAuthHeaders(),
            ['Accept' => 'application/json'],
        );

        return Http::withHeaders($headers)
            ->timeout(30)
            ->retry(2, 250);
    }

    /** Accept either a plain list or { data: [...] }; return null if neither. */
    private function extractRows($json): ?array
    {
        if (is_array($json) && array_is_list($json)) {
            return $json;
        }
        if (is_array($json) && isset($json['data']) && is_array($json['data'])) {
            return $json['data'];
        }
        return null;
    }

    /** Extract the external id from a row, accepting common key names. */
    private function extId(array $row): ?string
    {
        foreach (['id', 'external_id', 'uuid', 'guid'] as $key) {
            if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) {
                return (string) $row[$key];
            }
        }
        return null;
    }

    private function resolveDepartmentId(?string $externalDeptId): ?int
    {
        if (!$externalDeptId) return null;
        return Department::where('external_id', $externalDeptId)->value('id');
    }

    private function recordResult(
        ApiIntegration $integration,
        string $resource,
        string $status,
        ?string $error,
        array $stats
    ): array {
        $integration->fill([
            'last_synced_at'     => now(),
            'last_sync_resource' => $resource,
            'last_sync_status'   => $status,
            'last_sync_error'    => $error,
            'last_sync_stats'    => $stats,
        ])->save();

        return ['status' => $status, 'error' => $error, 'stats' => $stats];
    }
}
