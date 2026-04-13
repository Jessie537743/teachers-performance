<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FacultyDepartmentPosition;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Criterion;
use App\Models\Department;
use App\Models\FacultyProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FacultyController extends Controller
{
    private static function leadershipRoleByDepartmentId(int $departmentId): string
    {
        $departmentType = Department::query()
            ->whereKey($departmentId)
            ->value('department_type');

        return $departmentType === 'teaching' ? 'dean' : 'head';
    }

    private static function departmentPositionValue($position): string
    {
        return $position instanceof FacultyDepartmentPosition
            ? $position->value
            : $position;
    }

    private static function isDeanOrHeadPosition($position): bool
    {
        if ($position instanceof FacultyDepartmentPosition) {
            return $position === FacultyDepartmentPosition::DeanHead;
        }

        $deanHead = FacultyDepartmentPosition::DeanHead;

        return $position === $deanHead->value;
    }

    private function ensureDepartmentPositionAllowed(int $departmentId, $position): void
    {
        $departmentType = Department::query()
            ->whereKey($departmentId)
            ->value('department_type');

        $positionValue = self::departmentPositionValue($position);

        $deanHeadPosition = FacultyDepartmentPosition::DeanHead;
        $staffPosition = FacultyDepartmentPosition::Staff;

        if ($departmentType === 'non-teaching' && !in_array($positionValue, [
            $deanHeadPosition->value,
            $staffPosition->value,
        ], true)) {
            throw ValidationException::withMessages([
                'department_position' => 'For non-teaching departments, only Department Head and Staff are allowed.',
            ]);
        }
    }

    public function index(Request $request): View
    {
        Gate::authorize('manage-faculty');

        $filters = $request->validate([
            'search'         => ['nullable', 'string', 'max:255'],
            'department_id'   => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $search = isset($filters['search']) ? trim($filters['search']) : '';
        $departmentId = $filters['department_id'] ?? null;

        $faculty = User::with(['facultyProfile.department', 'department'])
            ->whereIn('role', ['faculty', 'dean', 'head', 'vp_acad', 'school_president'])
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%'))
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->where(function ($q2) use ($departmentId) {
                    $q2->where('department_id', $departmentId)
                        ->orWhereHas('facultyProfile', fn ($q3) => $q3->where('department_id', $departmentId));
                });
            })
            ->orderBy('name')
            ->paginate(25)
            ->appends($request->query());

        $departments = Department::where('is_active', true)
            ->orderBy('department_type')
            ->orderBy('name')
            ->get();

        $deansByDepartment = User::with(['facultyProfile.department'])
            ->whereIn('role', ['faculty', 'dean', 'head'])
            ->where('is_active', true)
            ->whereHas(
                'facultyProfile',
                fn ($q) => $q->where('department_position', FacultyDepartmentPosition::DeanHead)
            )
            ->orderBy('name')
            ->get()
            ->groupBy(function (User $u) {
                $dept = $u->facultyProfile?->department;

                return $dept?->name ?? '—';
            })
            ->sortKeys();

        $deanHeadEvaluationCriteria = Criterion::query()
            ->whereHas(
                'personnelTypes',
                fn ($q) => $q->whereIn('personnel_type', [
                    'dean_head_teaching',
                    'dean_head_non_teaching',
                ])
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('faculty.index', compact(
            'faculty',
            'departments',
            'deansByDepartment',
            'deanHeadEvaluationCriteria',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-faculty');

        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'email'                => ['required', 'email', 'unique:users,email'],
            'department_id'        => ['required', 'exists:departments,id'],
            'department_position'  => ['required', Rule::enum(FacultyDepartmentPosition::class)],
        ]);

        DB::transaction(function () use ($validated) {
            $this->ensureDepartmentPositionAllowed(
                (int) $validated['department_id'],
                $validated['department_position']
            );

            $isDeptHead = self::isDeanOrHeadPosition($validated['department_position']);
            $systemRole = $isDeptHead
                ? self::leadershipRoleByDepartmentId((int) $validated['department_id'])
                : 'faculty';
            // Default password is the email address; user must change on first login
            $user = User::create([
                'name'                => $validated['name'],
                'email'               => $validated['email'],
                'password'            => Hash::make($validated['email']),
                'role'                => $systemRole,
                'department_id'       => $validated['department_id'],
                'is_active'           => true,
                'must_change_password' => true,
            ]);

            FacultyProfile::create([
                'user_id'              => $user->id,
                'department_id'        => $validated['department_id'],
                'department_position'  => self::departmentPositionValue($validated['department_position']),
                'created_at'           => now(),
            ]);
        });

        Permission::clearCache();

        return redirect()->route('faculty.index')
            ->with('success', 'Faculty member created. Default password is the email address.');
    }

    public function update(Request $request, User $faculty): RedirectResponse
    {
        Gate::authorize('manage-faculty');

        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'email'                => ['required', 'email', 'unique:users,email,' . $faculty->id],
            'department_id'        => ['required', 'exists:departments,id'],
            'department_position'  => ['required', Rule::enum(FacultyDepartmentPosition::class)],
            'is_active'            => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($faculty, $validated) {
            $this->ensureDepartmentPositionAllowed(
                (int) $validated['department_id'],
                $validated['department_position']
            );

            $isDeptHead = self::isDeanOrHeadPosition($validated['department_position']);
            $newRole    = $faculty->role;

            if (in_array($faculty->role, ['vp_acad', 'school_president'], true)) {
                // Institution leaders evaluate all deans/heads; do not remap to dean/head/faculty.
                $newRole = $faculty->role;
            } elseif ($isDeptHead) {
                $newRole = self::leadershipRoleByDepartmentId((int) $validated['department_id']);
            } elseif (in_array($faculty->role, ['head', 'dean'], true)) {
                $newRole = 'faculty';
            }

            $previousEmail = $faculty->email;
            $newEmail      = $validated['email'];
            $emailChanged  = strcasecmp((string) $previousEmail, (string) $newEmail) !== 0;

            $userPayload = [
                'name'          => $validated['name'],
                'email'         => $newEmail,
                'department_id' => $validated['department_id'],
                'role'          => $newRole,
                'is_active'     => $validated['is_active'] ?? $faculty->is_active,
            ];

            // Default credential policy: password matches email; reset when email is changed.
            if ($emailChanged) {
                $userPayload['password']             = Hash::make($newEmail);
                $userPayload['must_change_password'] = true;
            }

            $faculty->update($userPayload);

            $profilePayload = [
                'department_id'       => $validated['department_id'],
                'department_position' => self::departmentPositionValue($validated['department_position']),
            ];

            if ($faculty->facultyProfile) {
                $faculty->facultyProfile->update($profilePayload);
            } else {
                FacultyProfile::create([
                    'user_id'             => $faculty->id,
                    'created_at'          => now(),
                    ...$profilePayload,
                ]);
            }
        });

        Permission::clearCache();

        return redirect()->route('faculty.index')
            ->with('success', 'Faculty member updated successfully.');
    }

    public function destroy(User $faculty): RedirectResponse
    {
        Gate::authorize('manage-faculty');

        $faculty->update(['is_active' => false]);

        return redirect()->route('faculty.index')
            ->with('success', 'Faculty member deactivated.');
    }

    public function bulkUpload(Request $request): RedirectResponse
    {
        Gate::authorize('manage-faculty');

        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $rows = $this->parseFacultyCsvRows($validated['csv_file']);
        if ($rows === []) {
            throw ValidationException::withMessages([
                'csv_file' => 'The uploaded CSV is empty.',
            ]);
        }

        $departments = Department::query()
            ->select(['id', 'name', 'code', 'department_type'])
            ->where('is_active', true)
            ->get();

        $departmentsByLookup = [];
        foreach ($departments as $department) {
            $nameKey = mb_strtolower(trim((string) $department->name));
            if ($nameKey !== '') {
                $departmentsByLookup[$nameKey] = $department;
            }

            $codeKey = mb_strtolower(trim((string) ($department->code ?? '')));
            if ($codeKey !== '') {
                $departmentsByLookup[$codeKey] = $department;
            }
        }

        $createdCount = 0;
        $skippedErrors = [];
        foreach ($rows as $lineNumber => $row) {
            try {
                $normalized = $this->normalizeBulkFacultyRow($row, $departmentsByLookup);

                DB::transaction(function () use ($normalized) {
                    $isLeadership = self::isDeanOrHeadPosition($normalized['department_position']);
                    $systemRole = $isLeadership
                        ? self::leadershipRoleByDepartmentId((int) $normalized['department_id'])
                        : 'faculty';

                    $user = User::create([
                        'name'                 => $normalized['name'],
                        'email'                => $normalized['email'],
                        'password'             => Hash::make($normalized['email']),
                        'role'                 => $systemRole,
                        'department_id'        => $normalized['department_id'],
                        'is_active'            => true,
                        'must_change_password' => true,
                    ]);

                    FacultyProfile::create([
                        'user_id'             => $user->id,
                        'department_id'       => $normalized['department_id'],
                        'department_position' => self::departmentPositionValue($normalized['department_position']),
                        'created_at'          => now(),
                    ]);
                });

                $createdCount++;
            } catch (\Throwable $e) {
                $skippedErrors[] = 'Row ' . $lineNumber . ': ' . $e->getMessage();
            }
        }

        $previewErrors = array_slice($skippedErrors, 0, 10);
        if (count($skippedErrors) > count($previewErrors)) {
            $previewErrors[] = '...and ' . (count($skippedErrors) - count($previewErrors)) . ' more row error(s).';
        }

        $message = $createdCount . ' faculty member(s) uploaded successfully.';
        if ($skippedErrors !== []) {
            $message .= ' ' . count($skippedErrors) . ' row(s) skipped.';
        }

        return redirect()
            ->route('faculty.index')
            ->with('success', $message)
            ->with('faculty_bulk_upload_errors', $previewErrors);
    }

    public function downloadBulkTemplate()
    {
        Gate::authorize('manage-faculty');

        $csv = implode("\n", [
            'full_name,email,department,role',
            '"Juan Dela Cruz",juan.delacruz@smcc.edu.ph,"College of Computing","Dean/Head"',
            '"Maria Santos",maria.santos@smcc.edu.ph,"Human Resource Office","Administrator/Head"',
            '"Pedro Cruz",pedro.cruz@smcc.edu.ph,"College of Computing","Faculty"',
        ]) . "\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="faculty-bulk-upload-template.csv"',
        ]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseFacultyCsvRows(UploadedFile $file): array
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

            $header = array_map(fn ($value) => $this->normalizeCsvHeader((string) $value), $rawHeader);

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
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
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
     * @param  array<string, Department>  $departmentsByLookup
     * @return array{name:string,email:string,department_id:int,department_position:FacultyDepartmentPosition}
     */
    private function normalizeBulkFacultyRow(array $row, array $departmentsByLookup): array
    {
        $name = trim((string) ($row['full_name'] ?? $row['name'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));
        $departmentRaw = mb_strtolower(trim((string) ($row['department'] ?? '')));
        $roleRaw = trim((string) ($row['role'] ?? ''));

        if ($name === '' || $email === '' || $departmentRaw === '' || $roleRaw === '') {
            throw new \InvalidArgumentException('Required columns: full_name, email, department, role.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format.');
        }

        $department = $departmentsByLookup[$departmentRaw] ?? null;
        if (! $department) {
            throw new \InvalidArgumentException('Department not found. Use exact department name or code.');
        }

        $departmentPosition = $this->parseDepartmentPositionFromRole($roleRaw, (string) $department->department_type);

        if (User::query()->where('email', $email)->exists()) {
            throw new \InvalidArgumentException('Email already exists.');
        }

        return [
            'name' => $name,
            'email' => $email,
            'department_id' => (int) $department->id,
            'department_position' => $departmentPosition,
        ];
    }

    private function parseDepartmentPositionFromRole(string $roleRaw, string $departmentType): FacultyDepartmentPosition
    {
        $normalized = mb_strtolower(trim($roleRaw));
        $normalized = str_replace(['_', '-', '/'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        $leadershipAliases = [
            'dean', 'head', 'dean head', 'administrator', 'administrator head', 'admin head',
        ];
        if (in_array($normalized, $leadershipAliases, true)) {
            return FacultyDepartmentPosition::DeanHead;
        }

        if ($normalized === 'program chair') {
            if ($departmentType === 'non-teaching') {
                throw new \InvalidArgumentException('Non-teaching departments allow Department Head or Staff only.');
            }
            return FacultyDepartmentPosition::ProgramChair;
        }
        if ($normalized === 'faculty' || $normalized === 'teacher' || $normalized === 'instructor') {
            if ($departmentType === 'non-teaching') {
                throw new \InvalidArgumentException('Non-teaching departments allow Department Head or Staff only.');
            }
            return FacultyDepartmentPosition::Faculty;
        }
        if ($normalized === 'staff') {
            return FacultyDepartmentPosition::Staff;
        }

        throw new \InvalidArgumentException(
            'Invalid role. Use Dean/Head (teaching), Administrator/Head (non-teaching), Program Chair, Faculty, or Staff.'
        );
    }
}
