<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyEvaluationPeriodOpenedJob;
use App\Jobs\RunStudentPromotionForPeriodJob;
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

    /**
     * Resource omits show — redirect accidental GET /evaluation-periods/{id} (bookmarks, bad links) to the list.
     */
    public function show(EvaluationPeriod $evaluationPeriod): RedirectResponse
    {
        Gate::authorize('manage-evaluation-periods');

        return redirect()->route('evaluation-periods.index');
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

        // If this period is being opened, promote for each currently open period, then close them
        if ($validated['is_open']) {
            $toPromoteIds = EvaluationPeriod::where('is_open', true)->pluck('id');
            EvaluationPeriod::where('is_open', true)->update(['is_open' => false]);
            foreach ($toPromoteIds as $periodId) {
                RunStudentPromotionForPeriodJob::dispatch($periodId)->afterResponse();
            }
        }

        $period = EvaluationPeriod::create($validated);

        EvaluationService::clearCache();

        if ($period->is_open) {
            NotifyEvaluationPeriodOpenedJob::dispatch($period->id)->afterResponse();
        }

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
        $wasOpen = $evaluationPeriod->is_open;

        // Opening this period: promote for every other open period, then close them
        if ($validated['is_open'] && ! $evaluationPeriod->is_open) {
            $otherIds = EvaluationPeriod::where('is_open', true)
                ->where('id', '!=', $evaluationPeriod->id)
                ->pluck('id');
            EvaluationPeriod::where('is_open', true)
                ->where('id', '!=', $evaluationPeriod->id)
                ->update(['is_open' => false]);
            foreach ($otherIds as $periodId) {
                RunStudentPromotionForPeriodJob::dispatch($periodId)->afterResponse();
            }
        }

        $evaluationPeriod->update($validated);

        // Closing this period: run promotion after save (period row still has correct semester/school_year)
        if ($wasOpen && ! $validated['is_open']) {
            RunStudentPromotionForPeriodJob::dispatch($evaluationPeriod->id)->afterResponse();
        }

        // Newly opened — notify all active evaluators
        if (! $wasOpen && $validated['is_open']) {
            NotifyEvaluationPeriodOpenedJob::dispatch($evaluationPeriod->id)->afterResponse();
        }

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
