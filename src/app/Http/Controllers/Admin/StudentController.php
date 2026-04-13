<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\EvaluationPeriod;
use App\Models\StudentProfile;
use App\Models\StudentSubjectAssignment;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-students');

        $search = trim((string) $request->query('search', ''));
        $departmentId = $request->query('department_id');
        $course = trim((string) $request->query('course', ''));
        $yearLevel = trim((string) $request->query('year_level', ''));
        $section = trim((string) $request->query('section', ''));

        $students = User::with(['studentProfile.department', 'studentProfile.subjectAssignments.subject'])
            ->whereHasRole('student')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->when(
                filled($departmentId) || $course !== '' || $yearLevel !== '' || $section !== '',
                function ($query) use ($departmentId, $course, $yearLevel, $section) {
                    $query->whereHas('studentProfile', function ($profileQuery) use ($departmentId, $course, $yearLevel, $section) {
                        if (filled($departmentId)) {
                            $profileQuery->where('department_id', (int) $departmentId);
                        }
                        if ($course !== '') {
                            $profileQuery->where('course', $course);
                        }
                        if ($yearLevel !== '') {
                            $profileQuery->where('year_level', $yearLevel);
                        }
                        if ($section !== '') {
                            $profileQuery->where('section', $section);
                        }
                    });
                }
            )
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $coursesFromCatalog = Course::query()
            ->where('is_active', true)
            ->whereNotNull('course_code')
            ->select('course_code')
            ->distinct()
            ->orderBy('course_code')
            ->pluck('course_code');
        $coursesFromProfiles = StudentProfile::query()
            ->whereNotNull('course')
            ->select('course')
            ->distinct()
            ->orderBy('course')
            ->pluck('course')
            ->values();
        $courses = $coursesFromCatalog
            ->merge($coursesFromProfiles)
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $yearLevels = StudentProfile::query()
            ->whereNotNull('year_level')
            ->select('year_level')
            ->distinct()
            ->orderBy('year_level')
            ->pluck('year_level')
            ->filter()
            ->values();
        $sections = StudentProfile::query()
            ->whereNotNull('section')
            ->select('section')
            ->pluck('section')
            ->map(fn ($value) => $this->normalizeSectionLabel((string) $value))
            ->filter(function (string $value): bool {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    return false;
                }

                // Exclude stray symbols like backticks/punctuation-only values.
                return (bool) preg_match('/[A-Za-z0-9]/', $trimmed);
            })
            ->unique()
            ->sort(function (string $left, string $right): int {
                $leftNum = ctype_digit($left) ? (int) $left : null;
                $rightNum = ctype_digit($right) ? (int) $right : null;

                if ($leftNum !== null && $rightNum !== null) {
                    return $leftNum <=> $rightNum;
                }

                return strnatcasecmp($left, $right);
            })
            ->values();
        [$defaultSemester, $defaultSchoolYear] = $this->resolveStudentTermDefaults();
        $subjectOptionsBySemester = $this->subjectOptionsBySemester();
        $semesterOptions = array_keys($subjectOptionsBySemester);
        $normalizedDefaultSemester = $this->normalizeSemesterToken($defaultSemester);
        if ($semesterOptions === []) {
            $semesterOptions = [$normalizedDefaultSemester];
            $subjectOptionsBySemester[$normalizedDefaultSemester] = [];
        } elseif (!in_array($normalizedDefaultSemester, $semesterOptions, true)) {
            $semesterOptions[] = $normalizedDefaultSemester;
            $subjectOptionsBySemester[$normalizedDefaultSemester] = [];
        }

        $viewData = compact(
            'students',
            'departments',
            'defaultSemester',
            'defaultSchoolYear',
            'search',
            'departmentId',
            'course',
            'yearLevel',
            'section',
            'courses',
            'yearLevels',
            'sections',
            'subjectOptionsBySemester',
            'semesterOptions'
        );

        if (view()->exists('students.index')) {
            return view('students.index', $viewData);
        }

        return view()->file(resource_path('views/students/index.blade.php'), $viewData);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-students');

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'student_id'    => ['required', 'string', 'max:50', 'unique:student_profiles,student_id'],
            'department_id' => ['required', 'exists:departments,id'],
            'course'        => ['required', 'string', 'max:100'],
            'year_level'    => ['required', 'integer', 'min:1', 'max:6'],
            'section'       => ['required', 'string', 'max:50'],
            'semester'      => ['required', 'string', 'max:20'],
            'school_year'   => ['required', 'string', 'max:20'],
            'student_status' => ['required', 'in:regular,irregular'],
            'selected_subject_ids' => ['nullable', 'array'],
            'selected_subject_ids.*' => ['integer', 'exists:subjects,id'],
        ]);

        DB::transaction(function () use ($validated) {
            $incomingStudentId = trim((string) $validated['student_id']);
            $email = $this->resolveStudentEmailValue($validated['email'] ?? null, $incomingStudentId);
            $normalizedSection = $this->normalizeSectionLabel((string) $validated['section']);

            $user = User::create([
                'name'                => $validated['name'],
                'email'               => $email,
                'username'            => $incomingStudentId,
                'password'            => Hash::make($incomingStudentId),
                'roles'               => ['student'],
                'department_id'       => $validated['department_id'],
                'is_active'           => true,
                'must_change_password' => true,
            ]);

            $profile = StudentProfile::create([
                'user_id'       => $user->id,
                'student_id'    => $validated['student_id'],
                'department_id' => $validated['department_id'],
                'course'        => $validated['course'],
                'year_level'    => $validated['year_level'],
                'section'       => $normalizedSection,
                'semester'      => $validated['semester'],
                'school_year'   => $validated['school_year'],
                'student_status' => $validated['student_status'],
                'created_at'    => now(),
            ]);

            $subjectIds = $validated['student_status'] === 'irregular'
                ? $this->selectedSubjectIdsForIrregularStudent(
                    $validated['selected_subject_ids'] ?? [],
                    (string) $validated['semester']
                )
                : $this->subjectIdsForEnrollment($validated);

            foreach ($subjectIds as $subjectId) {
                StudentSubjectAssignment::firstOrCreate([
                    'student_profile_id' => $profile->id,
                    'subject_id'         => $subjectId,
                ], [
                    'assigned_at' => now(),
                ]);
            }
        });

        return redirect()->route('students.index')
            ->with('success', 'Student created. Default username and password are the student ID.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('manage-students');

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'student_id'     => [
                'required',
                'string',
                'max:50',
                Rule::unique('student_profiles', 'student_id')->ignore($user->studentProfile?->id),
            ],
            'department_id'  => ['required', 'exists:departments,id'],
            'course'         => ['required', 'string', 'max:100'],
            'year_level'     => ['required', 'integer', 'min:1', 'max:6'],
            'section'        => ['required', 'string', 'max:50'],
            'semester'       => ['required', 'string', 'max:20'],
            'school_year'    => ['required', 'string', 'max:20'],
            'student_status' => ['required', 'in:regular,irregular'],
            'is_active'      => ['sometimes', 'boolean'],
            'selected_subject_ids' => ['nullable', 'array'],
            'selected_subject_ids.*' => ['integer', 'exists:subjects,id'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $previousUsername = $user->username;
            $incomingStudentId = trim((string) $validated['student_id']);
            $incomingEmail = $this->resolveStudentEmailValue($validated['email'] ?? null, $incomingStudentId);
            $shouldResetPasswordToStudentId = blank($previousUsername) || $previousUsername !== $incomingStudentId;
            $normalizedSection = $this->normalizeSectionLabel((string) $validated['section']);

            $user->update([
                'name'          => $validated['name'],
                'email'         => $incomingEmail,
                'username'      => $incomingStudentId,
                'department_id' => $validated['department_id'],
                'is_active'     => $validated['is_active'] ?? $user->is_active,
            ]);

            if ($shouldResetPasswordToStudentId) {
                $user->update([
                    'password'             => Hash::make($incomingStudentId),
                    'must_change_password' => true,
                ]);
            }

            if ($user->studentProfile) {
                $user->studentProfile->update([
                    'student_id'     => $incomingStudentId,
                    'department_id'  => $validated['department_id'],
                    'course'         => $validated['course'],
                    'year_level'     => $validated['year_level'],
                    'section'        => $normalizedSection,
                    'semester'       => $validated['semester'],
                    'school_year'    => $validated['school_year'],
                    'student_status' => $validated['student_status'],
                ]);

                $subjectIds = $validated['student_status'] === 'irregular'
                    ? $this->selectedSubjectIdsForIrregularStudent(
                        $validated['selected_subject_ids'] ?? [],
                        (string) $validated['semester']
                    )
                    : $this->subjectIdsForEnrollment($validated);
                $user->studentProfile->subjectAssignments()->delete();

                foreach ($subjectIds as $subjectId) {
                    StudentSubjectAssignment::create([
                        'student_profile_id' => $user->studentProfile->id,
                        'subject_id'         => $subjectId,
                        'assigned_at'        => now(),
                    ]);
                }
            }
        });

        return redirect()->route('students.index')
            ->with('success', 'Student updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('manage-students');

        $user = User::findOrFail($id);
        $user->update(['is_active' => false]);

        return redirect()->route('students.index')
            ->with('success', 'Student deactivated.');
    }

    public function bulkUpload(Request $request): RedirectResponse
    {
        Gate::authorize('manage-students');

        // Bulk imports can exceed default PHP time limits when hashing many passwords.
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        [$defaultSemester, $defaultSchoolYear] = $this->resolveStudentTermDefaults();
        $rows = $this->parseStudentCsvRows($validated['csv_file']);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'csv_file' => 'The uploaded CSV is empty.',
            ]);
        }

        $departmentsByName = Department::query()
            ->select(['id', 'name'])
            ->get()
            ->mapWithKeys(fn (Department $department) => [mb_strtolower(trim($department->name)) => $department->id])
            ->all();

        $createdCount = 0;
        $skippedErrors = [];

        foreach ($rows as $lineNumber => $row) {
            try {
                $normalized = $this->normalizeBulkStudentRow(
                    row: $row,
                    departmentsByName: $departmentsByName,
                    semester: $defaultSemester,
                    schoolYear: $defaultSchoolYear,
                );

                DB::transaction(function () use ($normalized) {
                    $incomingStudentId = trim((string) $normalized['student_id']);
                    $email = $this->resolveStudentEmailValue($normalized['email'] ?? null, $incomingStudentId);

                    $user = User::create([
                        'name'                 => $normalized['name'],
                        'email'                => $email,
                        'username'             => $incomingStudentId,
                        // Use a slightly lower bcrypt cost only for bulk import throughput.
                        'password'             => Hash::make($incomingStudentId, ['rounds' => 8]),
                        'roles'                => ['student'],
                        'department_id'        => $normalized['department_id'],
                        'is_active'            => true,
                        'must_change_password' => true,
                    ]);

                    $profile = StudentProfile::create([
                        'user_id'         => $user->id,
                        'student_id'      => $incomingStudentId,
                        'department_id'   => $normalized['department_id'],
                        'course'          => $normalized['course'],
                        'year_level'      => $normalized['year_level'],
                        'section'         => $normalized['section'],
                        'semester'        => $normalized['semester'],
                        'school_year'     => $normalized['school_year'],
                        'student_status'  => $normalized['student_status'],
                        'created_at'      => now(),
                    ]);

                    $subjectIds = $this->subjectIdsForEnrollment($normalized);
                    foreach ($subjectIds as $subjectId) {
                        StudentSubjectAssignment::firstOrCreate([
                            'student_profile_id' => $profile->id,
                            'subject_id'         => $subjectId,
                        ], [
                            'assigned_at' => now(),
                        ]);
                    }
                });

                $createdCount++;
            } catch (\Throwable $e) {
                $skippedErrors[] = 'Row ' . $lineNumber . ': ' . $e->getMessage();
            }
        }

        $maxPreview = 3;
        $errorPreview = array_slice($skippedErrors, 0, $maxPreview);
        $remainingErrors = count($skippedErrors) - count($errorPreview);
        $errorPreview = array_map(
            fn (string $message): string => mb_strimwidth($message, 0, 160, '...'),
            $errorPreview
        );

        $message = $createdCount . ' student(s) uploaded successfully.';
        if (count($skippedErrors) > 0) {
            $message .= ' ' . count($skippedErrors) . ' row(s) skipped.';
            if ($remainingErrors > 0) {
                $errorPreview[] = '...and ' . $remainingErrors . ' more row error(s).';
            }
        }

        return redirect()
            ->route('students.index')
            ->with('success', $message)
            ->with('bulk_upload_errors', $errorPreview);
    }

    public function downloadBulkTemplate()
    {
        Gate::authorize('manage-students');

        $csv = implode("\n", [
            'name,student_id,email,department,course,year_level,section,student_status',
            '"Juan Dela Cruz",2026-00001,,College of Computing,BSIT,1,1,regular',
            '"Maria Santos",2026-00002,maria@example.com,College of Computing,BSCS,2,2,irregular',
        ]) . "\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="students-bulk-upload-template.csv"',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return list<int>
     */
    private function subjectIdsForEnrollment(array $validated): array
    {
        $section = $this->normalizeSectionLabel((string) $validated['section']);
        $variants = $this->sectionQueryVariants($section);

        return Subject::query()
            ->where('department_id', $validated['department_id'])
            ->where('course', $validated['course'])
            ->where('year_level', (string) $validated['year_level'])
            ->where(function ($query) use ($variants) {
                $query->whereIn('section', $variants);
            })
            ->where('semester', 'like', $validated['semester'] . '%')
            ->pluck('id')
            ->all();
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveStudentTermDefaults(): array
    {
        $period = EvaluationPeriod::query()
            ->where('is_open', true)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first()
            ?? EvaluationPeriod::query()
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first();

        if ($period) {
            return [
                (string) ($period->semester ?: '1st'),
                (string) ($period->school_year ?: (now()->year . '-' . (now()->year + 1))),
            ];
        }

        return ['1st', now()->year . '-' . (now()->year + 1)];
    }

    private function resolveStudentEmailValue(mixed $incomingEmail, string $studentId): ?string
    {
        $email = filled($incomingEmail) ? trim((string) $incomingEmail) : null;

        if ($email !== null) {
            return $email;
        }

        if ($this->isUsersEmailNullable()) {
            return null;
        }

        $localPart = strtolower((string) preg_replace('/[^a-z0-9._-]+/i', '.', $studentId));
        $localPart = trim($localPart, '.-_');

        if ($localPart === '') {
            $localPart = 'student';
        }

        return $localPart . '@students.local';
    }

    private function isUsersEmailNullable(): bool
    {
        static $nullable = null;

        if ($nullable !== null) {
            return $nullable;
        }

        try {
            $row = DB::selectOne("
                SELECT IS_NULLABLE
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'email'
                LIMIT 1
            ");

            $isNullable = strtoupper((string) ($row->IS_NULLABLE ?? $row->is_nullable ?? 'NO')) === 'YES';
            $nullable = $isNullable;
        } catch (\Throwable) {
            $nullable = false;
        }

        return $nullable;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseStudentCsvRows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'csv_file' => 'Unable to read the uploaded file.',
            ]);
        }

        try {
            $rawHeader = fgetcsv($handle);
            if ($rawHeader === false) {
                return [];
            }

            $header = array_map(function ($value) {
                return $this->normalizeCsvHeader($this->decodeCsvValueToUtf8((string) $value));
            }, $rawHeader);

            $rows = [];
            $lineNumber = 1;
            while (($data = fgetcsv($handle)) !== false) {
                $lineNumber++;

                if ($this->isCsvRowEmpty($data)) {
                    continue;
                }

                $assoc = [];
                foreach ($header as $idx => $columnName) {
                    if ($columnName === '') {
                        continue;
                    }
                    $assoc[$columnName] = $this->decodeCsvValueToUtf8((string) ($data[$idx] ?? ''));
                }

                $rows[$lineNumber] = $assoc;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function normalizeCsvHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
        $normalized = strtolower(trim($header));
        $normalized = str_replace([' ', '-'], '_', $normalized);
        return preg_replace('/[^a-z0-9_]+/', '', $normalized) ?? '';
    }

    private function decodeCsvValueToUtf8(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        if (mb_check_encoding($trimmed, 'UTF-8')) {
            return $trimmed;
        }

        // Common CSV sources (Excel/Windows) may save text in cp1252/latin1.
        $converted = @mb_convert_encoding($trimmed, 'UTF-8', 'Windows-1252');
        if (is_string($converted) && $converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
            return trim($converted);
        }

        $converted = @mb_convert_encoding($trimmed, 'UTF-8', 'ISO-8859-1');
        if (is_string($converted) && $converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
            return trim($converted);
        }

        return preg_replace('/[^\x20-\x7E]/', '', $trimmed) ?? $trimmed;
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isCsvRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<string, int>  $departmentsByName
     * @return array<string, mixed>
     */
    private function normalizeBulkStudentRow(
        array $row,
        array $departmentsByName,
        string $semester,
        string $schoolYear
    ): array {
        $name = trim((string) ($row['name'] ?? ''));
        $studentId = trim((string) ($row['student_id'] ?? ''));
        $course = trim((string) ($row['course'] ?? ''));
        $section = $this->normalizeSectionLabel((string) ($row['section'] ?? ''));
        $studentStatus = strtolower(trim((string) ($row['student_status'] ?? 'regular')));
        $email = trim((string) ($row['email'] ?? ''));

        if ($name === '' || $studentId === '' || $course === '' || $section === '') {
            throw new \InvalidArgumentException('Required columns missing. Required: name, student_id, department, course, year_level, section.');
        }

        $yearRaw = trim((string) ($row['year_level'] ?? ''));
        $yearLevel = (int) $yearRaw;
        if ($yearRaw === '' || $yearLevel < 1 || $yearLevel > 6) {
            throw new \InvalidArgumentException('Invalid year_level. Use a value between 1 and 6.');
        }

        if (!in_array($studentStatus, ['regular', 'irregular'], true)) {
            throw new \InvalidArgumentException('Invalid student_status. Use regular or irregular.');
        }

        $departmentId = null;
        $departmentName = mb_strtolower(trim((string) ($row['department'] ?? '')));
        if ($departmentName !== '' && isset($departmentsByName[$departmentName])) {
            $departmentId = $departmentsByName[$departmentName];
        }

        if ($departmentId === null) {
            throw new \InvalidArgumentException('Department not found. Provide exact department name.');
        }

        if (User::query()->where('username', $studentId)->exists()) {
            throw new \InvalidArgumentException('Student ID already exists as username.');
        }

        if (StudentProfile::query()->where('student_id', $studentId)->exists()) {
            throw new \InvalidArgumentException('Student ID already exists.');
        }

        if ($email !== '' && User::query()->where('email', $email)->exists()) {
            throw new \InvalidArgumentException('Email already exists.');
        }

        return [
            'name'           => $name,
            'email'          => $email === '' ? null : $email,
            'student_id'     => $studentId,
            'department_id'  => $departmentId,
            'course'         => $course,
            'year_level'     => $yearLevel,
            'section'        => $section,
            'semester'       => $semester,
            'school_year'    => $schoolYear,
            'student_status' => $studentStatus,
        ];
    }

    /**
     * @return array<string, list<array{id:int,label:string}>>
     */
    private function subjectOptionsBySemester(): array
    {
        $grouped = Subject::query()
            ->whereNotNull('semester')
            ->where('semester', '!=', '')
            ->select(['id', 'code', 'title', 'semester'])
            ->orderBy('semester')
            ->orderBy('code')
            ->orderBy('title')
            ->get()
            ->groupBy(fn (Subject $subject) => $this->normalizeSemesterToken((string) $subject->semester))
            ->map(function ($subjects) {
                return $subjects->map(function (Subject $subject) {
                    $label = trim(((string) $subject->code) . ' - ' . ((string) $subject->title));
                    if ($label === '-' || $label === '') {
                        $label = 'Subject #' . $subject->id;
                    }

                    return [
                        'id' => (int) $subject->id,
                        'label' => $label,
                    ];
                })->values()->all();
            });

        $sortedKeys = $grouped
            ->keys()
            ->sortBy(fn (string $semester) => $this->semesterSortOrder($semester))
            ->values();

        $result = [];
        foreach ($sortedKeys as $semester) {
            $result[$semester] = $grouped->get($semester, []);
        }

        return $result;
    }

    /**
     * @param  array<int, mixed>  $selectedSubjectIds
     * @return list<int>
     */
    private function selectedSubjectIdsForIrregularStudent(array $selectedSubjectIds, string $semester): array
    {
        $ids = collect($selectedSubjectIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            throw ValidationException::withMessages([
                'selected_subject_ids' => 'Select at least one subject for irregular students.',
            ]);
        }

        $semesterToken = $this->normalizeSemesterToken($semester);

        $subjects = Subject::query()
            ->whereIn('id', $ids)
            ->select(['id', 'semester'])
            ->get();

        if ($subjects->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'selected_subject_ids' => 'One or more selected subjects are invalid.',
            ]);
        }

        $invalidBySemester = $subjects->first(function (Subject $subject) use ($semesterToken) {
            return $this->normalizeSemesterToken((string) $subject->semester) !== $semesterToken;
        });

        if ($invalidBySemester) {
            throw ValidationException::withMessages([
                'selected_subject_ids' => 'Selected subjects must match the chosen semester.',
            ]);
        }

        return $ids;
    }

    private function normalizeSemesterToken(string $semester): string
    {
        $value = strtolower(trim($semester));
        $map = [
            '1st semester' => '1st',
            'first semester' => '1st',
            '1st sem' => '1st',
            '2nd semester' => '2nd',
            'second semester' => '2nd',
            '2nd sem' => '2nd',
            '2nd semest' => '2nd',
        ];

        return $map[$value] ?? $value;
    }

    private function semesterSortOrder(string $semester): int
    {
        return match ($this->normalizeSemesterToken($semester)) {
            '1st' => 1,
            '2nd' => 2,
            'summer' => 3,
            default => 9,
        };
    }

    private function normalizeSectionLabel(string $value): string
    {
        $section = trim($value);
        if ($section === '') {
            return '';
        }

        if (preg_match('/^section\s*([0-9]+)$/i', $section, $matches)) {
            return (string) ((int) $matches[1]);
        }

        if (preg_match('/^[0-9]+$/', $section)) {
            return (string) ((int) $section);
        }

        $upper = strtoupper($section);
        if (preg_match('/^[A-Z]$/', $upper)) {
            $number = ord($upper) - ord('A') + 1;
            return (string) $number;
        }

        return preg_replace('/\s+/', ' ', $section) ?? $section;
    }

    /**
     * @return list<string>
     */
    private function sectionQueryVariants(string $normalizedSection): array
    {
        $variants = [];
        $normalized = $this->normalizeSectionLabel($normalizedSection);
        if ($normalized !== '') {
            $variants[] = $normalized;
        }

        if (preg_match('/^([0-9]+)$/', $normalized, $matches)) {
            $number = (int) $matches[1];
            $variants[] = 'Section ' . $number;
            $variants[] = (string) $number;
            if ($number >= 1 && $number <= 26) {
                $variants[] = chr(ord('A') + $number - 1);
            }
        }

        return array_values(array_unique(array_filter($variants)));
    }
}
