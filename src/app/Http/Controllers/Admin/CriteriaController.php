<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Criterion;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CriteriaController extends Controller
{
    /** @var list<string> */
    private const PERSONNEL_TYPES = ['teaching', 'non-teaching', 'dean_head_teaching', 'dean_head_non_teaching'];

    public function index(): View
    {
        Gate::authorize('manage-criteria');

        $criteria = Criterion::with(['questions', 'evaluatorGroups', 'personnelTypes'])
            ->orderBy('name')
            ->get();

        $criteriaTotal = $criteria->count();

        $groupKeys = ['student', 'dean', 'self', 'peer'];
        $criteriaByGroup = collect($groupKeys)->mapWithKeys(function (string $key) use ($criteria) {
            return [
                $key => $criteria
                    ->filter(
                        fn (Criterion $c) => $c->evaluatorGroups->pluck('evaluator_group')->contains($key)
                    )
                    ->values(),
            ];
        });

        return view('criteria.index', compact('criteriaByGroup', 'criteriaTotal', 'groupKeys'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-criteria');

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'weight'              => ['nullable', 'numeric', 'between:0,100'],
            'personnel_types'     => ['required', 'array', 'min:1'],
            'personnel_types.*'   => ['string', Rule::in(self::PERSONNEL_TYPES)],
            'evaluator_groups'    => ['required', 'array', 'min:1'],
            'evaluator_groups.*'  => ['string', Rule::in(['student', 'dean', 'self', 'peer'])],
            'questions'           => ['required', 'array', 'min:1'],
            'questions.*'         => ['required', 'array'],
            'questions.*.text'    => ['required', 'string', 'max:1000'],
            'questions.*.weight'  => ['nullable', 'numeric', 'between:0,100'],
        ]);

        DB::transaction(function () use ($validated) {
            $defaultWeight = (float) ($validated['weight'] ?? 0);

            $criterion = Criterion::create([
                'name'   => $validated['name'],
                'weight' => $defaultWeight,
            ]);

            foreach (array_values(array_unique($validated['personnel_types'])) as $personnelType) {
                $criterion->personnelTypes()->create(['personnel_type' => $personnelType]);
            }

            // On create, every selected evaluator group starts with the same weight.
            // Editing later via a specific tab will only change that tab's row.
            foreach (array_values(array_unique($validated['evaluator_groups'])) as $group) {
                $criterion->evaluatorGroups()->create([
                    'evaluator_group' => $group,
                    'weight'          => $defaultWeight,
                ]);
            }

            foreach ($validated['questions'] as $row) {
                $text = trim((string) ($row['text'] ?? ''));
                if ($text === '') {
                    continue;
                }
                Question::create([
                    'criteria_id'   => $criterion->id,
                    'question_text' => $text,
                    'weight'        => $row['weight'] ?? 0,
                ]);
            }
        });

        return redirect()->route('criteria.index')
            ->with('success', 'Criterion and questions created successfully.');
    }

    public function update(Request $request, Criterion $criterion): RedirectResponse
    {
        Gate::authorize('manage-criteria');

        $validated = $request->validate([
            'name'                     => ['required', 'string', 'max:255'],
            'weight'                   => ['nullable', 'numeric', 'between:0,100'],
            'editing_evaluator_group'  => ['nullable', 'string', Rule::in(['student', 'dean', 'self', 'peer'])],
            'personnel_types'          => ['required', 'array', 'min:1'],
            'personnel_types.*'        => ['string', Rule::in(self::PERSONNEL_TYPES)],
            'evaluator_groups'         => ['required', 'array', 'min:1'],
            'evaluator_groups.*'       => ['string', Rule::in(['student', 'dean', 'self', 'peer'])],
            'questions'                => ['required', 'array', 'min:1'],
            'questions.*.id'           => ['nullable', 'integer', 'exists:questions,id'],
            'questions.*.text'         => ['required', 'string', 'max:1000'],
            'questions.*.weight'       => ['nullable', 'numeric', 'between:0,100'],
        ]);

        DB::transaction(function () use ($criterion, $validated) {
            $existingQuestions = $criterion->questions()->get()->keyBy('id');

            // Track existing per-group weights so a membership-sync below doesn't
            // erase weights for groups still attached after this save.
            $existingPivotWeights = $criterion->evaluatorGroups()
                ->pluck('weight', 'evaluator_group')
                ->all();

            $editingGroup    = $validated['editing_evaluator_group'] ?? null;
            $submittedWeight = isset($validated['weight']) ? (float) $validated['weight'] : null;
            $newGroups       = array_values(array_unique($validated['evaluator_groups']));

            // Criterion-level fields. We keep `criteria.weight` updated as a
            // "default for newly-added groups" — never as the single source of
            // truth for any tab. When the user is editing on a specific tab,
            // we DO NOT push that tab's weight up to criteria.weight.
            $criterion->update([
                'name'   => $validated['name'],
                'weight' => $editingGroup === null && $submittedWeight !== null
                    ? $submittedWeight
                    : $criterion->weight,
            ]);

            $criterion->personnelTypes()->delete();
            foreach (array_values(array_unique($validated['personnel_types'])) as $personnelType) {
                $criterion->personnelTypes()->create(['personnel_type' => $personnelType]);
            }

            // Non-destructive evaluator-group sync: remove only groups that were
            // unchecked, add new ones, and leave existing rows (and their per-group
            // weights) alone unless the user is editing this very group.
            $criterion->evaluatorGroups()
                ->whereNotIn('evaluator_group', $newGroups)
                ->delete();

            foreach ($newGroups as $group) {
                $isEditingThisGroup = $editingGroup === $group;
                $previousWeight     = $existingPivotWeights[$group] ?? null;

                $weightForRow = $isEditingThisGroup && $submittedWeight !== null
                    ? $submittedWeight
                    : ($previousWeight ?? (float) ($criterion->weight ?? 0));

                $criterion->evaluatorGroups()->updateOrCreate(
                    ['evaluator_group' => $group],
                    ['weight' => $weightForRow],
                );
            }

            // Replace all questions with updated values from edit form.
            $criterion->questions()->delete();

            foreach ($validated['questions'] as $questionRow) {
                $questionText = trim((string) ($questionRow['text'] ?? ''));
                if ($questionText === '') {
                    continue;
                }

                $questionId = (int) ($questionRow['id'] ?? 0);
                $responseType = 'likert';

                if ($questionId > 0 && $existingQuestions->has($questionId)) {
                    $responseType = $existingQuestions->get($questionId)?->response_type ?? 'likert';
                }

                Question::create([
                    'criteria_id'   => $criterion->id,
                    'question_text' => $questionText,
                    'response_type' => $responseType,
                    'weight'        => $questionRow['weight'] ?? 0,
                ]);
            }
        });

        return redirect()->route('criteria.index')
            ->with('success', 'Criterion and questions updated successfully.');
    }

    public function destroy(Criterion $criterion): RedirectResponse
    {
        Gate::authorize('manage-criteria');

        DB::transaction(function () use ($criterion) {
            $criterion->questions()->delete();
            $criterion->delete();
        });

        return redirect()->route('criteria.index')
            ->with('success', 'Criterion and its questions deleted.');
    }
}
