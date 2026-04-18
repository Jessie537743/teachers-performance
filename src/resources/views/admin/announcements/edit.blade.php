@extends('layouts.app')
@section('title', 'Edit Announcement')
@section('page-title', 'Edit Announcement')
@section('content')
<div class="max-w-5xl">
    @include('admin.announcements._form', ['action' => route('admin.announcements.update', $announcement), 'method' => 'PUT', 'announcement' => $announcement])
</div>
@endsection
