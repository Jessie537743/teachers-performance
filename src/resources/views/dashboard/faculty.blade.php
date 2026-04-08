@extends('layouts.app')

@section('title', 'Faculty Dashboard')
@section('page-title', 'Faculty Dashboard')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Welcome, {{ auth()->user()->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">Faculty Dashboard</p>
    </div>
</div>

@canany(['submit-self-evaluation', 'submit-peer-evaluation'])
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
    <div class="text-center py-12 px-5">
        <div class="text-lg font-semibold mb-2 text-gray-900">Evaluations</div>
        <p class="text-gray-400 mb-4">Access your self and peer evaluations from the Evaluations page.</p>
        <a href="{{ route('evaluate.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">Go to Evaluations</a>
    </div>
</div>
@endcanany
@endsection
