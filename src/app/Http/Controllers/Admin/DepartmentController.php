<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-departments');

        $departments = Department::withCount([
            'facultyProfiles as faculty_count',
            'studentProfiles as student_count',
        ])->orderBy('name')->paginate(25);

        return view('departments.index', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-departments');

        $validated = $request->validate([
            'code'            => ['required', 'string', 'max:20', 'unique:departments,code'],
            'name'            => ['required', 'string', 'max:255', 'unique:departments,name'],
            'department_type' => ['required', 'string', 'in:teaching,non-teaching'],
        ]);

        $validated['is_active'] = true;

        Department::create($validated);

        return redirect()->route('departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        Gate::authorize('manage-departments');

        $validated = $request->validate([
            'code'            => ['required', 'string', 'max:20', 'unique:departments,code,' . $department->id],
            'name'            => ['required', 'string', 'max:255', 'unique:departments,name,' . $department->id],
            'department_type' => ['required', 'string', 'in:teaching,non-teaching'],
        ]);

        $department->update($validated);

        return redirect()->route('departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        Gate::authorize('manage-departments');

        // Soft-deactivate rather than hard delete to preserve relational integrity
        $department->update(['is_active' => false]);

        return redirect()->route('departments.index')
            ->with('success', 'Department deactivated.');
    }

    public function reactivate(Department $department): RedirectResponse
    {
        Gate::authorize('manage-departments');

        $department->update(['is_active' => true]);

        return redirect()->route('departments.index')
            ->with('success', 'Department reactivated.');
    }
}
