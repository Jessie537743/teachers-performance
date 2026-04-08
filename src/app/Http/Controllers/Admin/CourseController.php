<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-courses');

        $courses = Course::query()
            ->with('department')
            ->orderBy('department_id')
            ->orderBy('course_code')
            ->paginate(500);

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('courses.index', compact('courses', 'departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-courses');

        $validated = $request->validate([
            'course_code'   => [
                'required',
                'string',
                'max:50',
                Rule::unique('courses', 'course_code')
                    ->where(fn ($q) => $q
                        ->where('department_id', (int) $request->input('department_id'))
                        ->whereNull('semester')
                        ->whereNull('school_year')),
            ],
            'course_name'   => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        $validated['is_active'] = true;
        $validated['semester'] = null;
        $validated['school_year'] = null;

        Course::create($validated);

        return redirect()->route('courses.index')
            ->with('success', 'Course created successfully.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage-courses');

        $validated = $request->validate([
            'course_code'   => [
                'required',
                'string',
                'max:50',
                Rule::unique('courses', 'course_code')
                    ->where(fn ($q) => $q
                        ->where('department_id', (int) $request->input('department_id'))
                        ->whereNull('semester')
                        ->whereNull('school_year'))
                    ->ignore($course->id),
            ],
            'course_name'   => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'is_active'     => ['sometimes', 'boolean'],
        ]);

        $course->update($validated);

        return redirect()->route('courses.index')
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        Gate::authorize('manage-courses');

        $course->delete();

        return redirect()->route('courses.index')
            ->with('success', 'Course deleted.');
    }
}
