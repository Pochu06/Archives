@extends('layouts.app')
@section('title', 'Adviser Dashboard')
@section('page-title', 'Adviser Dashboard')
@section('page-subtitle', 'Review assignments and processing activity')
@section('content')
<div class="space-y-6">
    @include('dashboards.partials.analytics', ['analytics' => $analytics])
</div>
@endsection
