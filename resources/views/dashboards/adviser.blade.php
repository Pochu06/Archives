@extends('layouts.app')
@section('title', 'Adviser Dashboard')
@section('page-title', 'Adviser Dashboard')
@section('page-subtitle', 'Monitor your students\' research submissions')
@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-orange-500 to-orange-700 rounded-2xl p-5 text-white shadow-lg">
            <i class="fas fa-file-alt text-2xl mb-3 opacity-80"></i>
            <p class="text-3xl font-extrabold">{{ $totalSubmissions }}</p>
            <p class="text-orange-100 text-sm">Total Submissions</p>
        </div>
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl p-5 text-white shadow-lg">
            <i class="fas fa-clock text-2xl mb-3 opacity-80"></i>
            <p class="text-3xl font-extrabold">{{ $pendingReview }}</p>
            <p class="text-yellow-100 text-sm">Pending Review</p>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-2xl p-5 text-white shadow-lg">
            <i class="fas fa-check-circle text-2xl mb-3 opacity-80"></i>
            <p class="text-3xl font-extrabold">{{ $approved }}</p>
            <p class="text-green-100 text-sm">Approved</p>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl p-5 text-white shadow-lg">
            <i class="fas fa-user-graduate text-2xl mb-3 opacity-80"></i>
            <p class="text-3xl font-extrabold">{{ $totalStudents }}</p>
            <p class="text-blue-100 text-sm">My Students</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100 flex justify-between">
            <h3 class="font-bold text-gray-800 text-lg">Recent Student Submissions</h3>
            <a href="{{ route('adviser.submissions') }}" class="text-orange-600 text-sm font-semibold hover:underline">View All</a>
        </div>
        <div class="divide-y">
            @forelse($recentResearch as $r)
            <div class="p-4 flex items-center space-x-4 hover:bg-orange-50/30">
                <div class="bg-orange-100 p-2.5 rounded-xl"><i class="fas fa-file-alt text-orange-600"></i></div>
                <div class="flex-1 min-w-0">
                    <a href="{{ route('research.show', $r->id) }}" class="font-semibold text-gray-800 text-sm hover:text-orange-600 block truncate">{{ $r->title }}</a>
                    <p class="text-xs text-gray-500">{{ $r->user->name ?? 'Unknown' }} &bull; {{ $r->created_at->diffForHumans() }}</p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full {{ $r->status_badge }} font-medium">{{ ucfirst($r->status) }}</span>
                @if($r->status === 'pending')
                <form action="{{ route('research.approve', $r->id) }}" method="POST">
                    @csrf
                    <button class="text-xs bg-green-500 text-white px-3 py-1 rounded-full hover:bg-green-600">Approve</button>
                </form>
                @endif
            </div>
            @empty
            <p class="p-6 text-center text-gray-500">No submissions from your students yet.</p>
            @endforelse
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <a href="{{ route('adviser.submissions') }}" class="flex items-center bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-200 transition">
            <div class="bg-orange-100 p-3 rounded-xl mr-4"><i class="fas fa-tasks text-orange-600 text-xl"></i></div>
            <div><p class="font-bold text-gray-800">Review Submissions</p><p class="text-xs text-gray-500">Approve or reject student research</p></div>
        </a>
        <a href="{{ route('adviser.students') }}" class="flex items-center bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-200 transition">
            <div class="bg-blue-100 p-3 rounded-xl mr-4"><i class="fas fa-user-graduate text-blue-600 text-xl"></i></div>
            <div><p class="font-bold text-gray-800">My Students</p><p class="text-xs text-gray-500">View student roster</p></div>
        </a>
    </div>
</div>
@endsection
