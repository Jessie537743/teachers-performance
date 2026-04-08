<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $students = User::with(['studentProfile.department'])
            ->where('role', 'student')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        [$defaultSemester, $defaultSchoolYear] = $this->resolveStudentTermDefaults();

        return view('students.index', compact('students', 'departments', 'defaultSemester', 'defaultSchoolYear', 'search'));
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
                'role'                => 'student',
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

            $subjectIds = $this->subjectIdsForEnrollment($validated);

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

                $subjectIds = $this->subjectIdsForEnrollment($validated);
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
                        'password'             => Hash::make($incomingStudentId),
                        'role'                 => 'student',
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
