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

        return view('criteria.index', compact('criteriaByGroup', 'criteriaTotal'));
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
            $criterion = Criterion::create([
                'name'   => $validated['name'],
                'weight' => $validated['weight'] ?? 0,
            ]);

            foreach (array_values(array_unique($validated['personnel_types'])) as $personnelType) {
                $criterion->personnelTypes()->create(['personnel_type' => $personnelType]);
            }

            foreach (array_values(array_unique($validated['evaluator_groups'])) as $group) {
                $criterion->evaluatorGroups()->create(['evaluator_group' => $group]);
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
            'name'                 => ['required', 'string', 'max:255'],
            'weight'               => ['nullable', 'numeric', 'between:0,100'],
            'personnel_types'      => ['required', 'array', 'min:1'],
            'personnel_types.*'    => ['string', Rule::in(self::PERSONNEL_TYPES)],
            'evaluator_groups'     => ['required', 'array', 'min:1'],
            'evaluator_groups.*'   => ['string', Rule::in(['student', 'dean', 'self', 'peer'])],
            'questions'            => ['required', 'array', 'min:1'],
            'questions.*.id'       => ['nullable', 'integer', 'exists:questions,id'],
            'questions.*.text'     => ['required', 'string', 'max:1000'],
            'questions.*.weight'   => ['nullable', 'numeric', 'between:0,100'],
        ]);

        DB::transaction(function () use ($criterion, $validated) {
            $existingQuestions = $criterion->questions()->get()->keyBy('id');

            $criterion->update([
                'name'   => $validated['name'],
                'weight' => $validated['weight'] ?? $criterion->weight,
            ]);

            $criterion->personnelTypes()->delete();
            foreach (array_values(array_unique($validated['personnel_types'])) as $personnelType) {
                $criterion->personnelTypes()->create(['personnel_type' => $personnelType]);
            }

            $criterion->evaluatorGroups()->delete();
            foreach (array_values(array_unique($validated['evaluator_groups'])) as $group) {
                $criterion->evaluatorGroups()->create(['evaluator_group' => $group]);
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
