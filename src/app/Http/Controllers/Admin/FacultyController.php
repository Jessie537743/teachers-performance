<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FacultyDepartmentPosition;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Criterion;
use App\Models\Department;
use App\Models\FacultyProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class FacultyController extends Controller
{
    private static function departmentPositionValue(FacultyDepartmentPosition|string $position): string
    {
        return $position instanceof FacultyDepartmentPosition
            ? $position->value
            : $position;
    }

    private static function isDeanOrHeadPosition(FacultyDepartmentPosition|string $position): bool
    {
        if ($position instanceof FacultyDepartmentPosition) {
            return $position === FacultyDepartmentPosition::DeanHead;
        }

        return $position === FacultyDepartmentPosition::DeanHead->value;
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
            ->whereIn('role', ['faculty', 'head'])
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
            ->whereIn('role', ['faculty', 'head'])
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
            $isDeptHead = self::isDeanOrHeadPosition($validated['department_position']);
            // Default password is the email address; user must change on first login
            $user = User::create([
                'name'                => $validated['name'],
                'email'               => $validated['email'],
                'password'            => Hash::make($validated['email']),
                'role'                => $isDeptHead ? 'head' : 'faculty',
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
            $isDeptHead = self::isDeanOrHeadPosition($validated['department_position']);
            $newRole    = $faculty->role;
            if ($isDeptHead) {
                $newRole = 'head';
            } elseif ($faculty->role === 'head') {
                $newRole = 'faculty';
            }

            $faculty->update([
                'name'          => $validated['name'],
                'email'         => $validated['email'],
                'department_id' => $validated['department_id'],
                'role'          => $newRole,
                'is_active'     => $validated['is_active'] ?? $faculty->is_active,
            ]);

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
}
