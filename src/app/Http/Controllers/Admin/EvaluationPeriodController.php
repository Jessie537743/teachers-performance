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
        EvaluationService::getOpenEvaluationPeriod();

        $periods = EvaluationPeriod::orderBy('school_year', 'desc')
            ->orderBy('semester')
            ->get();

        return view('evaluation-periods.index', compact('periods'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-evaluation-periods');

        $validated = $request->validate([
            'school_year' => ['required', 'string', 'max:20'],
            'semester'    => ['required', 'string', 'max:20'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'is_open'     => ['sometimes', 'boolean'],
        ]);

        $validated['is_open'] = $request->boolean('is_open', false);

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
            'school_year' => ['required', 'string', 'max:20'],
            'semester'    => ['required', 'string', 'max:20'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'is_open'     => ['sometimes', 'boolean'],
        ]);

        $validated['is_open'] = $request->boolean('is_open', false);

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
