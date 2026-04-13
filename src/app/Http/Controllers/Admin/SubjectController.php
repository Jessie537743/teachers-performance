<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\FacultyProfile;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-subjects');

        $filters = $this->validatedSubjectFilters($request);

        $filteredBase = $this->subjectFilterQuery($filters);

        $subjects = (clone $filteredBase)
            ->with(['department', 'assignments.faculty.user'])
            ->orderBy('semester')
            ->orderBy('code')
            ->paginate(25)
            ->appends($request->query());

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $courses = Course::where('is_active', true)
            ->with('department:id,code,name')
            ->orderBy('department_id')
            ->orderBy('course_code')
            ->get();
        $faculties = FacultyProfile::with(['user', 'department:id,code'])
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy(fn (FacultyProfile $f) => mb_strtolower($f->user?->name ?? 'zz'))
            ->values();

        $coursesForAdd = $courses->map(function (Course $c) {
            $code = $c->department?->code ?? 'DEPT';
            $label = $code.' — '.$c->course_code;
            if (! empty($c->course_name)) {
                $label .= ' · '.$c->course_name;
            }

            return [
                'department_id' => $c->department_id,
                'value' => $c->department_id.'|'.$c->course_code,
                'label' => $label,
            ];
        })->values();

        $facultyListForSubjects = $faculties->map(fn (FacultyProfile $f) => [
            'id' => $f->id,
            'department_id' => $f->department_id,
            'name' => $f->user?->name ?? ('Faculty #'.$f->id),
            'dept_code' => $f->department?->code ?? '',
        ])->values();

        return view('subjects.index', compact(
            'subjects',
            'departments',
            'courses',
            'faculties',
            'coursesForAdd',
            'facultyListForSubjects',
        ));
    }

    public function show(Subject $subject): View
    {
        Gate::authorize('manage-subjects');

        $subject->load(['department', 'assignments.faculty.user']);

        $offerings = $this->subjectOfferingCluster($subject);

        return view('subjects.show', compact('subject', 'offerings'));
    }

    public function edit(Subject $subject): View
    {
        Gate::authorize('manage-subjects');

        $subject->load(['department', 'assignments.faculty.user']);

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $faculties = FacultyProfile::with(['user', 'department:id,code'])
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy(fn (FacultyProfile $f) => mb_strtolower($f->user?->name ?? 'zz'))
            ->values();

        $facultyListForSubjects = $faculties->map(fn (FacultyProfile $f) => [
            'id' => $f->id,
            'department_id' => $f->department_id,
            'name' => $f->user?->name ?? ('Faculty #'.$f->id),
            'dept_code' => $f->department?->code ?? '',
        ])->values();

        return view('subjects.edit', compact('subject', 'departments', 'facultyListForSubjects'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-subjects');

        if (! $request->filled('faculty_id')) {
            $request->merge(['faculty_id' => null]);
        }

        $validated = $request->validate([
            'code'               => ['required', 'string', 'max:50'],
            'title'              => ['required', 'string', 'max:255'],
            'department_ids'     => ['required', 'array', 'min:1'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'course_mode'        => ['required', 'in:specific,all'],
            'course_keys'        => ['nullable', 'array'],
            'course_keys.*'      => ['string', 'max:120'],
            'faculty_id'         => ['nullable', 'integer', 'exists:faculty_profiles,id'],
            'year_level'         => ['required', 'integer', 'min:1', 'max:6'],
            'sections'           => ['required', 'array', 'min:1'],
            'sections.*'         => ['required', 'string', 'max:50'],
            'semester'           => ['required', 'string', 'max:20'],
            'school_year'        => ['nullable', 'string', 'max:20'],
        ]);

        $departmentIds = array_values(array_unique(array_map('intval', $validated['department_ids'])));

        /** @var list<array{department_id: int, course: string}> $pairs */
        $pairs = [];

        if ($validated['course_mode'] === 'all') {
            foreach ($departmentIds as $deptId) {
                $codes = Course::query()
                    ->where('department_id', $deptId)
                    ->where('is_active', true)
                    ->pluck('course_code')
                    ->filter()
                    ->unique()
                    ->values();

                foreach ($codes as $courseCode) {
                    $pairs[] = ['department_id' => $deptId, 'course' => $courseCode];
                }
            }

            if ($pairs === []) {
                return back()
                    ->withInput()
                    ->withErrors(['course_mode' => 'No active courses found for the selected departments.']);
            }
        } else {
            $keys = array_values(array_unique(array_filter($validated['course_keys'] ?? [])));

            if ($keys === []) {
                return back()
                    ->withInput()
                    ->withErrors(['course_keys' => 'Select at least one course for the chosen departments.']);
            }

            foreach ($keys as $key) {
                if (! preg_match('/^(\d+)\|(.+)$/u', $key, $m)) {
                    return back()
                        ->withInput()
                        ->withErrors(['course_keys' => 'Invalid course selection.']);
                }

                $deptId = (int) $m[1];
                $courseCode = $m[2];

                if (! in_array($deptId, $departmentIds, true)) {
                    return back()
                        ->withInput()
                        ->withErrors(['course_keys' => 'Each selected course must belong to one of the selected departments.']);
                }

                $courseOk = Course::query()
                    ->where('department_id', $deptId)
                    ->where('course_code', $courseCode)
                    ->where('is_active', true)
                    ->exists();

                if (! $courseOk) {
                    return back()
                        ->withInput()
                        ->withErrors(['course_keys' => 'One or more selected courses are invalid or inactive.']);
                }

                $pairs[] = ['department_id' => $deptId, 'course' => $courseCode];
            }
        }

        $facultyId = $validated['faculty_id'] ?? null;

        $sections = array_values(array_unique(array_filter(array_map(
            fn ($s) => $this->normalizeSectionLabel((string) $s),
            $validated['sections'] ?? []
        ))));

        if ($sections === []) {
            return back()
                ->withInput()
                ->withErrors(['sections' => 'Select at least one valid section.']);
        }

        $createdCount = 0;
        $duplicateCount = 0;

        DB::transaction(function () use ($validated, $pairs, $facultyId, $sections, &$createdCount, &$duplicateCount) {
            foreach ($pairs as $row) {
                $deptId = $row['department_id'];
                $courseCode = $row['course'];

                foreach ($sections as $sectionStr) {
                    $exists = Subject::where('code', $validated['code'])
                        ->where('department_id', $deptId)
                        ->where('course', $courseCode)
                        ->where('year_level', (string) $validated['year_level'])
                        ->where('semester', $validated['semester'])
                        ->where('section', $sectionStr)
                        ->when(
                            isset($validated['school_year']) && $validated['school_year'] !== null && $validated['school_year'] !== '',
                            fn ($q) => $q->where('school_year', $validated['school_year']),
                            fn ($q) => $q->where(function ($w) {
                                $w->whereNull('school_year')->orWhere('school_year', '');
                            })
                        )
                        ->exists();

                    if ($exists) {
                        $duplicateCount++;

                        continue;
                    }

                    $subject = Subject::create([
                        'code'          => $validated['code'],
                        'title'         => $validated['title'],
                        'department_id' => $deptId,
                        'course'        => $courseCode,
                        'year_level'    => $validated['year_level'],
                        'section'       => $sectionStr,
                        'semester'      => $validated['semester'],
                        'school_year'   => $validated['school_year'] ?? null,
                    ]);

                    if ($facultyId !== null) {
                        $subject->assignments()->create([
                            'faculty_id' => $facultyId,
                        ]);
                    }

                    $createdCount++;
                }
            }
        });

        if ($createdCount === 0) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'No subjects were created because matching records already exist.']);
        }

        $message = $createdCount === 1
            ? 'Subject created successfully.'
            : "Subjects created successfully for {$createdCount} courses.";

        if ($duplicateCount > 0) {
            $message .= " {$duplicateCount} duplicate entr" . ($duplicateCount === 1 ? 'y was' : 'ies were') . ' skipped.';
        }

        return redirect()->route('subjects.index')
            ->with('success', $message);
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        Gate::authorize('manage-subjects');

        if (! $request->filled('faculty_id')) {
            $request->merge(['faculty_id' => null]);
        }

        $validated = $request->validate([
            'code'          => ['required', 'string', 'max:50'],
            'title'         => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'course'        => ['required', 'string', 'max:100'],
            'year_level'    => ['required', 'integer', 'min:1', 'max:6'],
            'section'       => ['required', 'string', 'max:50'],
            'semester'      => ['required', 'string', 'max:20'],
            'school_year'   => ['nullable', 'string', 'max:20'],
            'faculty_id'    => ['nullable', 'integer', 'exists:faculty_profiles,id'],
        ]);

        $validated['section'] = $this->normalizeSectionLabel($validated['section']);
        if ($validated['section'] === '') {
            return back()
                ->withInput()
                ->withErrors(['section' => 'Enter a valid section.']);
        }

        $duplicate = Subject::where('code', $validated['code'])
            ->where('department_id', $validated['department_id'])
            ->where('course', $validated['course'])
            ->where('year_level', (string) $validated['year_level'])
            ->where('semester', $validated['semester'])
            ->where('section', $validated['section'])
            ->where('id', '!=', $subject->id)
            ->when(
                filled($validated['school_year'] ?? null),
                fn ($q) => $q->where('school_year', $validated['school_year']),
                fn ($q) => $q->where(function ($w) {
                    $w->whereNull('school_year')->orWhere('school_year', '');
                })
            )
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'A subject with these details already exists.']);
        }

        DB::transaction(function () use ($subject, $validated) {
            $subject->update(collect($validated)->except('faculty_id')->all());

            $subject->assignments()->delete();
            if (($validated['faculty_id'] ?? null) !== null) {
                $subject->assignments()->create([
                    'faculty_id' => $validated['faculty_id'],
                ]);
            }
        });

        return redirect()->route('subjects.index')
            ->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        Gate::authorize('manage-subjects');

        $subject->update(['is_active' => false]);

        return redirect()->route('subjects.index')
            ->with('success', 'Subject deactivated.');
    }

    public function reactivate(Subject $subject): RedirectResponse
    {
        Gate::authorize('manage-subjects');

        $subject->update(['is_active' => true]);

        return redirect()->route('subjects.index')
            ->with('success', 'Subject reactivated.');
    }

    public function bulkUpload(Request $request): RedirectResponse
    {
        Gate::authorize('manage-subjects');

        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $rows = $this->parseSubjectCsvRows($validated['csv_file']);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'csv_file' => 'The uploaded CSV is empty.',
            ]);
        }

        $departmentsByName = Department::query()
            ->select(['id', 'name', 'code'])
            ->where('is_active', true)
            ->get()
            ->flatMap(function (Department $department): array {
                $pairs = [];
                $name = mb_strtolower(trim($department->name));
                if ($name !== '') {
                    $pairs[$name] = $department->id;
                }

                $code = mb_strtolower(trim((string) ($department->code ?? '')));
                if ($code !== '') {
                    $pairs[$code] = $department->id;
                }

                return $pairs;
            })
            ->all();

        $facultyByName = FacultyProfile::query()
            ->with('user:id,name')
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get()
            ->flatMap(function (FacultyProfile $faculty): array {
                $name = $faculty->user?->name;
                if (! $name) {
                    return [];
                }

                return [$this->normalizePersonName($name) => $faculty->id];
            })
            ->all();

        $createdCount = 0;
        $duplicateCount = 0;
        $skippedErrors = [];

        foreach ($rows as $lineNumber => $row) {
            try {
                $normalized = $this->normalizeBulkSubjectRow(
                    row: $row,
                    departmentsByName: $departmentsByName,
                    facultyByName: $facultyByName,
                );

                $exists = Subject::query()
                    ->where('code', $normalized['code'])
                    ->where('department_id', $normalized['department_id'])
                    ->where('course', $normalized['course'])
                    ->where('year_level', (string) $normalized['year_level'])
                    ->where('section', $normalized['section'])
                    ->where('semester', $normalized['semester'])
                    ->exists();

                if ($exists) {
                    $duplicateCount++;
                    continue;
                }

                DB::transaction(function () use ($normalized) {
                    $subject = Subject::create([
                        'code'          => $normalized['code'],
                        'title'         => $normalized['title'],
                        'department_id' => $normalized['department_id'],
                        'course'        => $normalized['course'],
                        'year_level'    => $normalized['year_level'],
                        'section'       => $normalized['section'],
                        'semester'      => $normalized['semester'],
                        'school_year'   => $normalized['school_year'],
                    ]);

                    if ($normalized['faculty_id'] !== null) {
                        $subject->assignments()->create([
                            'faculty_id' => $normalized['faculty_id'],
                        ]);
                    }
                });

                $createdCount++;
            } catch (\Throwable $e) {
                $skippedErrors[] = 'Row ' . $lineNumber . ': ' . $e->getMessage();
            }
        }

        if ($createdCount === 0 && $duplicateCount > 0 && $skippedErrors === []) {
            return back()
                ->withInput()
                ->withErrors(['csv_file' => 'No subjects were created because all uploaded rows are duplicates.']);
        }

        $message = $createdCount . ' subject row(s) uploaded successfully.';
        if ($duplicateCount > 0) {
            $message .= ' ' . $duplicateCount . ' duplicate row(s) skipped.';
        }

        if ($skippedErrors !== []) {
            $message .= ' ' . count($skippedErrors) . ' invalid row(s) skipped.';
        }

        $previewErrors = array_slice($skippedErrors, 0, 10);
        if (count($skippedErrors) > count($previewErrors)) {
            $previewErrors[] = '...and ' . (count($skippedErrors) - count($previewErrors)) . ' more row error(s).';
        }

        return redirect()
            ->route('subjects.index')
            ->with('success', $message)
            ->with('subject_bulk_upload_errors', $previewErrors);
    }

    public function downloadBulkTemplate()
    {
        Gate::authorize('manage-subjects');

        $csv = implode("\n", [
            'code,title,department,course,year_level,section,semester,school_year,instructor',
            '"GEC ELECT 3","Philippine Popular Culture","College of Computing","BSIT",2,A,2nd,2025-2026,"Mr. R. Japitana"',
            '"BPED 9","Swimming and Aquatics","College of Computing","BSCS",2,B,2nd,2025-2026,"Ms. H. Erong"',
        ]) . "\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="subjects-bulk-upload-template.csv"',
        ]);
    }

    /**
     * @return array{search: string, department_id: int|null, semester: string|null}
     */
    private function validatedSubjectFilters(Request $request): array
    {
        $filters = $request->validate([
            'search'         => ['nullable', 'string', 'max:255'],
            'department_id'  => ['nullable', 'integer', 'exists:departments,id'],
            'semester'       => ['nullable', 'string', 'in:1st,2nd,Summer'],
        ]);

        return [
            'search'         => isset($filters['search']) ? trim($filters['search']) : '',
            'department_id'  => $filters['department_id'] ?? null,
            'semester'       => $filters['semester'] ?? null,
        ];
    }

    /**
     * @param  array{search: string, department_id: int|null, semester: string|null}  $filters
     */
    private function subjectFilterQuery(array $filters): Builder
    {
        $search = $filters['search'];
        $departmentId = $filters['department_id'];
        $semesterFilter = $filters['semester'];

        return Subject::query()
            ->when($search !== '', function ($q) use ($search) {
                $escaped = addcslashes($search, '%_\\');
                $q->where(function ($q2) use ($escaped) {
                    $q2->where('title', 'like', '%'.$escaped.'%')
                        ->orWhere('code', 'like', '%'.$escaped.'%');
                });
            })
            ->when($departmentId, fn ($q, $id) => $q->where('department_id', $id))
            ->when($semesterFilter, fn ($q, $sem) => $q->where('semester', 'like', $sem.'%'));
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseSubjectCsvRows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'csv_file' => 'Unable to read uploaded CSV file.',
            ]);
        }

        try {
            $rawHeader = fgetcsv($handle);
            if ($rawHeader === false) {
                return [];
            }

            $header = array_map(
                fn ($value) => $this->normalizeCsvHeader((string) $value),
                $rawHeader
            );

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
                    $assoc[$columnName] = trim((string) ($data[$idx] ?? ''));
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
        $normalized = strtolower(trim($header));
        $normalized = str_replace([' ', '-'], '_', $normalized);
        return preg_replace('/[^a-z0-9_]+/', '', $normalized) ?? '';
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
     * @param  array<string, int>  $facultyByName
     * @return array{code:string,title:string,department_id:int,course:string,year_level:int,section:string,semester:string,school_year:?string,faculty_id:?int}
     */
    private function normalizeBulkSubjectRow(array $row, array $departmentsByName, array $facultyByName): array
    {
        $code = trim((string) ($row['code'] ?? ''));
        $title = trim((string) ($row['title'] ?? ''));
        $departmentRaw = mb_strtolower(trim((string) ($row['department'] ?? '')));
        $course = strtoupper(trim((string) ($row['course'] ?? '')));
        $yearRaw = trim((string) ($row['year_level'] ?? ''));
        $section = trim((string) ($row['section'] ?? ''));
        $semester = $this->normalizeSemester((string) ($row['semester'] ?? ''));
        $schoolYear = trim((string) ($row['school_year'] ?? ''));
        $instructor = trim((string) ($row['instructor'] ?? ''));

        if ($code === '' || $title === '' || $departmentRaw === '' || $course === '' || $yearRaw === '' || $section === '' || $semester === '') {
            throw new \InvalidArgumentException('Required columns: code, title, department, course, year_level, section, semester.');
        }

        $yearLevel = (int) $yearRaw;
        if ($yearLevel < 1 || $yearLevel > 6) {
            throw new \InvalidArgumentException('Invalid year_level. Use 1 to 6.');
        }

        $departmentId = $departmentsByName[$departmentRaw] ?? null;
        if ($departmentId === null) {
            throw new \InvalidArgumentException('Department not found. Use exact department name or code.');
        }

        $courseExists = Course::query()
            ->where('department_id', $departmentId)
            ->where('course_code', $course)
            ->where('is_active', true)
            ->exists();

        if (! $courseExists) {
            throw new \InvalidArgumentException('Course does not exist or is inactive for the given department.');
        }

        $facultyId = null;
        if ($instructor !== '') {
            $normInstructor = $this->normalizePersonName($instructor);
            $facultyId = $facultyByName[$normInstructor] ?? null;
            if ($facultyId === null) {
                throw new \InvalidArgumentException('Instructor not found among active faculty names.');
            }
        }

        return [
            'code' => $code,
            'title' => $title,
            'department_id' => $departmentId,
            'course' => $course,
            'year_level' => $yearLevel,
            'section' => $section,
            'semester' => $semester,
            'school_year' => $schoolYear !== '' ? $schoolYear : null,
            'faculty_id' => $facultyId,
        ];
    }

    private function normalizeSemester(string $raw): string
    {
        $v = mb_strtolower(trim($raw));
        return match ($v) {
            '1st', '1st semester', 'first', 'first semester' => '1st',
            '2nd', '2nd semester', 'second', 'second semester' => '2nd',
            'summer' => 'Summer',
            default => '',
        };
    }

    private function normalizePersonName(string $name): string
    {
        return trim((string) preg_replace(
            '/\s+/u',
            ' ',
            preg_replace('/[^a-z0-9]+/iu', ' ', preg_replace('/\b(mr|ms|mrs|dr|prof)\.?\b/iu', ' ', mb_strtolower($name)) ?? '') ?? ''
        ));
    }

    /**
     * Same program offering across sections (one row per section in the database).
     *
     * @return Collection<int, Subject>
     */
    private function subjectOfferingCluster(Subject $subject): Collection
    {
        $q = Subject::query()
            ->where('code', $subject->code)
            ->where('department_id', $subject->department_id)
            ->where('course', $subject->course)
            ->where('year_level', $subject->year_level)
            ->where('semester', $subject->semester);

        $sy = $subject->school_year;
        if ($sy === null || $sy === '') {
            $q->where(function ($w) {
                $w->whereNull('school_year')->orWhere('school_year', '');
            });
        } else {
            $q->where('school_year', $sy);
        }

        return $q->with(['department', 'assignments.faculty.user'])
            ->orderBy('section')
            ->get();
    }

    /**
     * Normalize section for subject offerings (numeric "01" → "1"; keep "A","B" as-is to avoid colliding with "1").
     */
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

        return preg_replace('/\s+/', ' ', $section) ?? $section;
    }
}
