@extends('layouts.app')
@section('title', 'New Announcement')
@section('page-title', 'New Announcement')
@section('content')
<div class="max-w-5xl">
    @include('admin.announcements._form', ['action' => route('admin.announcements.store'), 'method' => 'POST'])
</div>
@endsection
