@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Welcome, {{ auth()->user()->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ \App\Enums\Permission::roleLabel(auth()->user()->role) }}</p>
    </div>
</div>
@endsection
