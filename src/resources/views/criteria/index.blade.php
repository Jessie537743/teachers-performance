@extends('layouts.app')

@section('title', 'Criteria')
@section('page-title', 'Evaluation Criteria')

@section('content')
@php
    $evaluatorGroupLabels = [
        'student' => 'Student',
        'dean' => 'Dean / Head',
        'self' => 'Self',
        'peer' => 'Peer',
    ];
    $personnelTypeLabels = [
        'teaching' => 'Teaching',
        'non-teaching' => 'Non-teaching',
        'dean_head_teaching' => 'Dean/Head (teaching dept.)',
        'dean_head_non_teaching' => 'Dean/Head (non-teaching dept.)',
    ];
@endphp
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Evaluation Criteria</h1>
        <p class="text-sm text-gray-500 mt-1">Manage criteria and questions used in evaluations.</p>
    </div>
</div>

{{-- Add Button + Count --}}
<div class="flex gap-4 items-center mb-6">
    @can('manage-criteria')
    <button type="button" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm" onclick="document.getElementById('addCriterionModal').style.display='flex'">+ Add Criterion</button>
    @endcan
    <span class="text-gray-500 text-sm">{{ $criteriaTotal }} total</span>
</div>

{{-- Tabs for groups --}}
<div class="mb-5">
    <div class="flex gap-2 flex-wrap border-b-2 border-gray-200 pb-0">
        @foreach(['student' => 'Student', 'dean' => 'Dean / Head', 'self' => 'Self', 'peer' => 'Peer'] as $groupKey => $groupLabel)
            <button type="button"
                class="tab-btn px-4 py-2.5 rounded-t-xl font-semibold text-sm transition {{ request('group', 'student') === $groupKey ? 'bg-blue-600 text-white' : 'bg-gray-200 text-slate-900 hover:bg-gray-300' }}"
                onclick="showTab(event, '{{ $groupKey }}')">
                {{ $groupLabel }}
                @if(isset($criteriaByGroup[$groupKey]))
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-bold bg-white/30 ml-1">{{ $criteriaByGroup[$groupKey]->count() }}</span>
                @endif
            </button>
        @endforeach
    </div>
</div>

@foreach(['student' => 'Student', 'dean' => 'Dean / Head', 'self' => 'Self', 'peer' => 'Peer'] as $groupKey => $groupLabel)
<div class="tab-content" id="tab-{{ $groupKey }}" {!! request('group', 'student') === $groupKey ? '' : 'style="display:none;"' !!}>
    <h3 class="mb-3.5 text-gray-500 text-sm uppercase tracking-wider font-semibold">
        {{ $groupLabel }} Criteria
    </h3>

    @if(isset($criteriaByGroup[$groupKey]) && $criteriaByGroup[$groupKey]->count() > 0)
        @foreach($criteriaByGroup[$groupKey] as $criterion)
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md mb-4">
            <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <span class="font-bold text-slate-900">{{ $criterion->name }}</span>
                    @foreach($criterion->personnelTypes->sortBy('personnel_type') as $ptRow)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 ml-2">{{ $personnelTypeLabels[$ptRow->personnel_type] ?? ucfirst($ptRow->personnel_type) }}</span>
                    @endforeach
                    @foreach($criterion->evaluatorGroups->sortBy('evaluator_group') as $eg)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 ml-1">{{ $evaluatorGroupLabels[$eg->evaluator_group] ?? $eg->evaluator_group }}</span>
                    @endforeach
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 ml-1">{{ $criterion->questions->count() }} questions</span>
                </div>
                @can('manage-criteria')
                <div class="flex gap-1.5">
                    @php
                        $criterionPayload = [
                            'id' => $criterion->id,
                            'name' => $criterion->name,
                            'personnel_types' => $criterion->personnelTypes->pluck('personnel_type')->values(),
                            'groups' => $criterion->evaluatorGroups->pluck('evaluator_group')->values(),
                            'questions' => $criterion->questions
                                ->map(fn ($q) => [
                                    'id' => $q->id,
                                    'text' => $q->question_text,
                                    'response_type' => $q->response_type ?? 'likert',
                                ])
                                ->values(),
                        ];
                    @endphp
                    <button type="button"
                        class="edit-criterion-btn inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-gray-300 transition"
                        data-criterion-id="{{ $criterion->id }}"
                        onclick="openEditCriterionById({{ $criterion->id }})">
                        Edit
                    </button>
                    <script type="application/json" id="criterion-json-{{ $criterion->id }}">
                        @json($criterionPayload)
                    </script>
                    <form method="POST" action="{{ route('criteria.destroy', $criterion->id) }}"
                          onsubmit="return confirm('Delete this criterion and all its questions?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-2 bg-red-600 text-white px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition">Delete</button>
                    </form>
                </div>
                @endcan
            </div>
            @if($criterion->questions->count() > 0)
            <div class="p-5 pt-3">
                <ol class="pl-5 m-0 list-decimal">
                    @foreach($criterion->questions as $question)
                        <li class="py-1.5 text-sm border-b border-gray-100 text-slate-700">
                            {{ $question->question_text }}
                            @if(($question->response_type ?? 'likert') === 'dean_recommendation')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-violet-100 text-violet-800 ml-2">Recommendation</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
            @endif
        </div>
        @endforeach
    @else
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
            <div class="p-5 text-center text-gray-500 py-8">
                No criteria defined for the {{ $groupLabel }} evaluator group.
            </div>
        </div>
    @endif
</div>
@endforeach

{{-- Add Criterion Modal --}}
<div id="addCriterionModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[520px] max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="mb-5 text-lg font-bold text-slate-900">Add Criterion</h3>
        <form method="POST" action="{{ route('criteria.store') }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="mb-4 sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="cr_name">Criterion Name</label>
                    <input type="text" name="name" id="cr_name" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                           placeholder="e.g. Teaching Effectiveness" value="{{ old('name') }}" required>
                </div>
                <div class="mb-4 sm:col-span-2">
                    <span class="block text-sm font-semibold text-slate-700 mb-1.5">Personnel types</span>
                    <p class="text-xs text-gray-500 mb-2">Dean/Head (teaching/non-teaching dept.) applies to academic administrators (e.g. college deans). Their default rubric is <strong>EVALUATION FOR ACADEMIC ADMINISTRATORS</strong>, seeded under Evaluation Criteria; adjust questions there as needed.</p>
                    @error('personnel_types')
                        <p class="text-sm text-red-600 mb-2">{{ $message }}</p>
                    @enderror
                    <div class="flex flex-wrap gap-x-6 gap-y-2 rounded-xl border border-gray-200 bg-gray-50/80 px-3.5 py-3">
                        @foreach($personnelTypeLabels as $val => $label)
                        <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-slate-700">
                            <input type="checkbox" name="personnel_types[]" value="{{ $val }}"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                   {{ in_array($val, old('personnel_types', []), true) ? 'checked' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="mb-4 sm:col-span-2">
                    <span class="block text-sm font-semibold text-slate-700 mb-1.5">Evaluator groups</span>
                    <p class="text-xs text-gray-500 mb-2">Select one or more. This criterion will appear in each selected evaluator&rsquo;s form.</p>
                    @error('evaluator_groups')
                        <p class="text-sm text-red-600 mb-2">{{ $message }}</p>
                    @enderror
                    <div class="flex flex-wrap gap-x-6 gap-y-2 rounded-xl border border-gray-200 bg-gray-50/80 px-3.5 py-3">
                        @foreach($evaluatorGroupLabels as $val => $label)
                        <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-slate-700">
                            <input type="checkbox" name="evaluator_groups[]" value="{{ $val }}"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                   {{ in_array($val, old('evaluator_groups', []), true) ? 'checked' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Questions</label>
                <div id="questionsContainer">
                    <div class="question-input-row flex gap-2 mb-2">
                        <input type="text" name="questions[]" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                               placeholder="Question 1" value="{{ old('questions.0') }}" required>
                        <button type="button" class="inline-flex items-center gap-2 bg-red-600 text-white px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition shrink-0" onclick="removeQuestion(this)">Remove</button>
                    </div>
                </div>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-gray-300 transition" onclick="addQuestion()">+ Add Question</button>
            </div>

            <div class="flex gap-2.5 mt-1.5">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Save Criterion</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" onclick="document.getElementById('addCriterionModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Criterion Modal --}}
<div id="editCriterionModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[520px] max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="mb-5 text-lg font-bold text-slate-900">Edit Criterion</h3>
        <form method="POST" id="editCriterionForm">
            @csrf @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Criterion Name</label>
                <input type="text" name="name" id="ecr_name" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
            </div>
            <div class="mb-4">
                <span class="block text-sm font-semibold text-slate-700 mb-1.5">Personnel types</span>
                <p class="text-xs text-gray-500 mb-2">Select categories that apply.</p>
                @error('personnel_types')
                    <p class="text-sm text-red-600 mb-2">{{ $message }}</p>
                @enderror
                <div class="flex flex-wrap gap-x-6 gap-y-2 rounded-xl border border-gray-200 bg-gray-50/80 px-3.5 py-3">
                    @foreach($personnelTypeLabels as $val => $label)
                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-slate-700">
                        <input type="checkbox" name="personnel_types[]" value="{{ $val }}"
                               class="ecr-pt-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="mb-4">
                <span class="block text-sm font-semibold text-slate-700 mb-1.5">Evaluator groups</span>
                <p class="text-xs text-gray-500 mb-2">Select one or more.</p>
                @error('evaluator_groups')
                    <p class="text-sm text-red-600 mb-2">{{ $message }}</p>
                @enderror
                <div class="flex flex-wrap gap-x-6 gap-y-2 rounded-xl border border-gray-200 bg-gray-50/80 px-3.5 py-3" id="editEvalGroupsBox">
                    @foreach($evaluatorGroupLabels as $val => $label)
                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-slate-700">
                        <input type="checkbox" name="evaluator_groups[]" value="{{ $val }}"
                               class="ecr-eg-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-3">Edit existing questions directly or add new ones.</p>
            <div id="editQuestionsContainer"></div>
            <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-gray-300 transition mb-4" onclick="addEditQuestion()">+ Add Question</button>
            <div class="flex gap-2.5">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Save Changes</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" onclick="document.getElementById('editCriterionModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
@if($errors->any() && !old('_method'))
    document.getElementById('addCriterionModal').style.display = 'flex';
@endif

const addCriterionModal = document.getElementById('addCriterionModal');
if (addCriterionModal) {
    addCriterionModal.addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
}

const editCriterionModal = document.getElementById('editCriterionModal');
if (editCriterionModal) {
    editCriterionModal.addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
}

document.querySelectorAll('.edit-criterion-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.getAttribute('data-criterion-id');
        if (id) {
            openEditCriterionById(id);
        }
    });
});

var questionCount = 1;
var editQuestionCount = 0;

function addQuestion() {
    questionCount++;
    var container = document.getElementById('questionsContainer');
    var row = document.createElement('div');
    row.className = 'question-input-row flex gap-2 mb-2';
    row.innerHTML =
        '<input type="text" name="questions[]" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" placeholder="Question ' + questionCount + '" required>' +
        '<button type="button" class="inline-flex items-center gap-2 bg-red-600 text-white px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition shrink-0" onclick="removeQuestion(this)">Remove</button>';
    container.appendChild(row);
}

function removeQuestion(btn) {
    var rows = document.querySelectorAll('#questionsContainer .question-input-row');
    if (rows.length > 1) {
        btn.parentElement.remove();
    }
}

function addEditQuestion() {
    var container = document.getElementById('editQuestionsContainer');
    editQuestionCount++;
    var row = document.createElement('div');
    row.className = 'edit-question-row flex gap-2 mb-2 items-start';
    row.innerHTML =
        '<input type="hidden" name="questions[' + editQuestionCount + '][id]" value="">' +
        '<input type="text" name="questions[' + editQuestionCount + '][text]" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" placeholder="Question ' + editQuestionCount + '" required>' +
        '<button type="button" class="inline-flex items-center gap-2 bg-red-600 text-white px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition shrink-0" onclick="removeEditQuestion(this)">Remove</button>';
    container.appendChild(row);
}

function removeEditQuestion(btn) {
    var rows = document.querySelectorAll('#editQuestionsContainer .edit-question-row');
    if (rows.length > 1) {
        var row = btn.closest('.edit-question-row');
        if (row) {
            row.remove();
        }
    }
}

function escapeHtml(value) {
    return String(value === null || typeof value === 'undefined' ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function openEditCriterion(button) {
    var rawPayload = (button && button.dataset && button.dataset.criterion)
        ? button.dataset.criterion
        : '{}';
    var payload = {};
    try {
        payload = JSON.parse(rawPayload);
    } catch (e) {
        payload = {};
    }

    var id = payload.id;
    var name = payload.name || '';
    var personnelTypes = payload.personnel_types || [];
    var groups = payload.groups || [];
    var questions = payload.questions || [];

    if (!id) {
        return;
    }

    document.getElementById('editCriterionForm').action = @json(url('criteria')) + '/' + id;
    document.getElementById('ecr_name').value = name;
    var ptSet = new Set(Array.isArray(personnelTypes) ? personnelTypes : (personnelTypes ? [personnelTypes] : []));
    document.querySelectorAll('.ecr-pt-checkbox').forEach(function(cb) {
        cb.checked = ptSet.has(cb.value);
    });
    var selected = new Set(Array.isArray(groups) ? groups : (groups ? [groups] : []));
    document.querySelectorAll('.ecr-eg-checkbox').forEach(function(cb) {
        cb.checked = selected.has(cb.value);
    });
    var container = document.getElementById('editQuestionsContainer');
    container.innerHTML = '';
    editQuestionCount = 0;

    var safeQuestions = Array.isArray(questions) ? questions : [];
    safeQuestions.forEach(function(q) {
        editQuestionCount++;
        var qId = q && q.id ? q.id : '';
        var qText = q && q.text ? q.text : '';
        var row = document.createElement('div');
        row.className = 'edit-question-row flex gap-2 mb-2 items-start';
        var recommendationBadge = q && q.response_type === 'dean_recommendation'
            ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-violet-100 text-violet-800 mt-2 whitespace-nowrap">Recommendation</span>'
            : '';

        row.innerHTML =
            '<input type="hidden" name="questions[' + editQuestionCount + '][id]" value="' + escapeHtml(qId) + '">' +
            '<input type="text" name="questions[' + editQuestionCount + '][text]" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" value="' + escapeHtml(qText) + '" required>' +
            recommendationBadge +
            '<button type="button" class="inline-flex items-center gap-2 bg-red-600 text-white px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition shrink-0" onclick="removeEditQuestion(this)">Remove</button>';
        container.appendChild(row);
    });

    if (safeQuestions.length === 0) {
        addEditQuestion();
    }

    document.getElementById('editCriterionModal').style.display = 'flex';
}

function openEditCriterionById(id) {
    var dataNode = document.getElementById('criterion-json-' + id);
    if (!dataNode) {
        return;
    }

    var payload = {};
    try {
        payload = JSON.parse(dataNode.textContent || '{}');
    } catch (e) {
        payload = {};
    }

    openEditCriterion({
        dataset: {
            criterion: JSON.stringify(payload),
        },
    });
}

function showTab(event, key) {
    document.querySelectorAll('.tab-content').forEach(function(el) {
        el.style.display = 'none';
    });
    document.getElementById('tab-' + key).style.display = 'block';
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.classList.remove('bg-blue-600', 'text-white');
        btn.classList.add('bg-gray-200', 'text-slate-900', 'hover:bg-gray-300');
    });
    event.currentTarget.classList.remove('bg-gray-200', 'text-slate-900', 'hover:bg-gray-300');
    event.currentTarget.classList.add('bg-blue-600', 'text-white');
}
</script>
@endpush
