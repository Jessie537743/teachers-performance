<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiFeatureImportance;
use App\Models\AiModelMetric;
use App\Models\EvaluationPeriod;
use App\Services\MlApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModelTrainingController extends Controller
{
    public function index(): View
    {
        $this->authorizeAccess();

        $periods = EvaluationPeriod::query()
            ->select(['semester', 'school_year'])
            ->orderByDesc('school_year')
            ->orderBy('semester')
            ->get();

        $recentMetrics = AiModelMetric::query()
            ->where('model_name', 'Random Forest')
            ->orderByDesc('training_date')
            ->limit(10)
            ->get();

        $latestMetric = $recentMetrics->first();
        $latestFeatureImportance = collect();

        if ($latestMetric) {
            $featureQuery = AiFeatureImportance::query()
                ->where('model_name', 'Random Forest');

            if ($latestMetric->semester === null) {
                $featureQuery->whereNull('semester');
            } else {
                $featureQuery->where('semester', $latestMetric->semester);
            }

            if ($latestMetric->school_year === null) {
                $featureQuery->whereNull('school_year');
            } else {
                $featureQuery->where('school_year', $latestMetric->school_year);
            }

            $latestRecordedDate = (clone $featureQuery)->max('recorded_date');
            if ($latestRecordedDate) {
                $latestFeatureImportance = $featureQuery
                    ->where('recorded_date', $latestRecordedDate)
                    ->orderByDesc('importance_score')
                    ->get();
            }
        }

        return view('admin.model-training', compact(
            'periods',
            'recentMetrics',
            'latestMetric',
            'latestFeatureImportance'
        ));
    }

    public function train(Request $request, MlApiService $mlApiService): RedirectResponse
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'semester' => ['nullable', 'string', 'max:20'],
            'school_year' => ['nullable', 'string', 'max:20'],
        ]);

        $semester = $validated['semester'] ?: null;
        $schoolYear = $validated['school_year'] ?: null;

        if (($semester && !$schoolYear) || (!$semester && $schoolYear)) {
            return back()
                ->withInput()
                ->with('error', 'Choose both semester and school year, or leave both blank for all terms.');
        }

        $result = $mlApiService->trainCurrentTerm($semester, $schoolYear);

        if (isset($result['error'])) {
            return back()
                ->withInput()
                ->with('error', "Training failed: {$result['error']}");
        }

        $scope = ($semester && $schoolYear)
            ? "for {$semester} {$schoolYear}"
            : 'for all historical terms';

        return redirect()
            ->route('model-training.index')
            ->with('success', "Random Forest training completed {$scope}.")
            ->with('training_result', $result);
    }

    private function authorizeAccess(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasRole(['admin', 'dean']), 403);
    }
}
