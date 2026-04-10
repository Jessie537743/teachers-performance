@extends('layouts.app')

@section('title', 'Model Training')
@section('page-title', 'Model Training')

@section('content')
<div class="mb-5 animate-slide-up">
    <h1 class="text-2xl font-bold text-gray-900">Random Forest Training</h1>
    <p class="mt-1 text-sm text-gray-500">Train the model using all historical records or a specific semester and school year.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-1">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Train Model</h2>
            <form method="POST" action="{{ route('model-training.train') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="semester" class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                    <select id="semester" name="semester" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">All semesters</option>
                        @foreach($periods->pluck('semester')->filter()->unique() as $semester)
                            <option value="{{ $semester }}" @selected(old('semester') === $semester)>{{ $semester }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="school_year" class="block text-sm font-medium text-gray-700 mb-1">School Year</label>
                    <select id="school_year" name="school_year" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">All school years</option>
                        @foreach($periods->pluck('school_year')->filter()->unique() as $schoolYear)
                            <option value="{{ $schoolYear }}" @selected(old('school_year') === $schoolYear)>{{ $schoolYear }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center px-4 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                    Train Random Forest
                </button>
                <p class="text-xs text-gray-500">Tip: choose both semester and school year to run term-specific training.</p>
            </form>
        </div>

        @if(session('training_result'))
            @php($result = session('training_result'))
            <div class="mt-5 bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                <h2 class="text-base font-semibold text-gray-900 mb-3">Latest Run Result</h2>
                <div class="space-y-1.5 text-sm text-gray-700">
                    <div><span class="font-medium">Model:</span> {{ $result['model_used'] ?? 'Random Forest' }}</div>
                    <div><span class="font-medium">Data Source:</span> {{ $result['data_source'] ?? 'MySQL' }}</div>
                    <div><span class="font-medium">Scope:</span>
                        @if(!empty($result['requested_semester']) && !empty($result['requested_school_year']))
                            {{ $result['requested_semester'] }} / {{ $result['requested_school_year'] }}
                        @else
                            All historical terms
                        @endif
                    </div>
                    <div><span class="font-medium">Accuracy:</span> {{ $result['accuracy'] ?? '—' }}</div>
                    <div><span class="font-medium">F1 Score:</span> {{ $result['f1_score'] ?? '—' }}</div>
                    <div><span class="font-medium">Records Used:</span> {{ $result['records_used'] ?? '—' }}</div>
                </div>
            </div>
        @endif
    </div>

    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="text-base font-semibold text-gray-900">Recent Training Metrics</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Scope</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Accuracy</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Precision</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Recall</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">F1</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Samples</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentMetrics as $metric)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-gray-700">{{ optional($metric->training_date)->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    @if($metric->semester && $metric->school_year)
                                        {{ format_semester($metric->semester) }} / {{ $metric->school_year }}
                                    @else
                                        All terms
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $metric->accuracy ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $metric->precision_score ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $metric->recall_score ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $metric->f1_score ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $metric->training_samples ?? '—' }} / {{ $metric->testing_samples ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400">No training metrics available yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Latest Feature Importance</h2>
                @if($latestMetric)
                    <span class="text-xs text-gray-500">
                        {{ $latestMetric->semester && $latestMetric->school_year ? format_semester($latestMetric->semester) . ' / ' . $latestMetric->school_year : 'All terms' }}
                    </span>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Feature</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Importance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($latestFeatureImportance as $feature)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-gray-700">{{ $feature->feature_name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $feature->importance_score }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-8 text-center text-gray-400">No feature importance records yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
