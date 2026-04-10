<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationPeriod;
use App\Services\EvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EvaluationPeriodController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-evaluation-periods');

        $periods = EvaluationPeriod::orderBy('school_year', 'desc')
            ->orderBy('semester')
            ->get();

        return view('evaluation-periods.index', compact('periods'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-evaluation-periods');

        $validated = $request->validate([
            'school_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{4}$/'],
            'semester'    => ['required', 'string', 'max:20', 'in:1st Semester,2nd Semester,Summer'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'is_open'     => ['sometimes', 'boolean'],
        ], [
            'school_year.regex' => 'School year must be in the format YYYY-YYYY (e.g. 2025-2026).',
            'semester.in'       => 'Semester must be 1st Semester, 2nd Semester, or Summer.',
        ]);

        // Reject illogical year ranges (e.g. 2026-2024).
        [$startYear, $endYear] = array_map('intval', explode('-', $validated['school_year']));
        if ($endYear !== $startYear + 1) {
            return back()
                ->withErrors(['school_year' => 'School year must span two consecutive years (e.g. 2025-2026).'])
                ->withInput();
        }

        $validated['semester'] = canonical_semester($validated['semester']) ?? $validated['semester'];
        $validated['is_open']  = $request->boolean('is_open', false);

        // If this period is being opened, close all others
        if ($validated['is_open']) {
            EvaluationPeriod::where('is_open', true)->update(['is_open' => false]);
        }

        EvaluationPeriod::create($validated);

        EvaluationService::clearCache();

        return redirect()->route('evaluation-periods.index')
            ->with('success', 'Evaluation period created successfully.');
    }

    public function update(Request $request, EvaluationPeriod $evaluationPeriod): RedirectResponse
    {
        Gate::authorize('manage-evaluation-periods');

        $validated = $request->validate([
            'school_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{4}$/'],
            'semester'    => ['required', 'string', 'max:20', 'in:1st Semester,2nd Semester,Summer'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'is_open'     => ['sometimes', 'boolean'],
        ], [
            'school_year.regex' => 'School year must be in the format YYYY-YYYY (e.g. 2025-2026).',
            'semester.in'       => 'Semester must be 1st Semester, 2nd Semester, or Summer.',
        ]);

        // Reject illogical year ranges (e.g. 2026-2024).
        [$startYear, $endYear] = array_map('intval', explode('-', $validated['school_year']));
        if ($endYear !== $startYear + 1) {
            return back()
                ->withErrors(['school_year' => 'School year must span two consecutive years (e.g. 2025-2026).'])
                ->withInput();
        }

        $validated['semester'] = canonical_semester($validated['semester']) ?? $validated['semester'];
        $validated['is_open']  = $request->boolean('is_open', false);

        // Only one period may be open at a time
        if ($validated['is_open'] && !$evaluationPeriod->is_open) {
            EvaluationPeriod::where('is_open', true)
                ->where('id', '!=', $evaluationPeriod->id)
                ->update(['is_open' => false]);
        }

        $evaluationPeriod->update($validated);

        EvaluationService::clearCache();

        return redirect()->route('evaluation-periods.index')
            ->with('success', 'Evaluation period updated.');
    }

    public function destroy(EvaluationPeriod $evaluationPeriod): RedirectResponse
    {
        Gate::authorize('manage-evaluation-periods');

        $evaluationPeriod->delete();

        EvaluationService::clearCache();

        return redirect()->route('evaluation-periods.index')
            ->with('success', 'Evaluation period deleted.');
    }
}
