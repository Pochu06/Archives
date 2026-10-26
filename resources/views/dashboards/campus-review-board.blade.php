@extends('layouts.app')
@section('title', 'Campus Review Board')
@section('page-title', 'Campus Review Board')
@section('page-subtitle', 'Review research submissions before they proceed to the RDE Office')
@section('content')
<div class="space-y-6">
    @include('dashboards.partials.analytics', ['analytics' => $analytics])
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl bg-violet-700 p-5 text-white shadow">
            <i class="fas fa-hourglass-half mb-3 text-2xl"></i>
            <p class="text-3xl font-extrabold">{{ $pendingBoardReview }}</p>
            <p class="text-sm text-violet-100">Awaiting Board review</p>
        </div>
        <div class="rounded-2xl bg-amber-500 p-5 text-white shadow">
            <i class="fas fa-rotate-left mb-3 text-2xl"></i>
            <p class="text-3xl font-extrabold">{{ $returnedByBoard }}</p>
            <p class="text-sm text-amber-100">Returned for revisions</p>
        </div>
        <div class="rounded-2xl bg-green-700 p-5 text-white shadow">
            <i class="fas fa-circle-check mb-3 text-2xl"></i>
            <p class="text-3xl font-extrabold">{{ $approvedByBoard }}</p>
            <p class="text-sm text-green-100">Approved by the Board</p>
        </div>
        <div class="rounded-2xl bg-red-700 p-5 text-white shadow">
            <i class="fas fa-circle-xmark mb-3 text-2xl"></i>
            <p class="text-3xl font-extrabold">{{ $rejectedByBoard }}</p>
            <p class="text-sm text-red-100">Rejected by the Board</p>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-5">
            <div>
                <h2 class="font-bold text-gray-900">Pending review</h2>
                <p class="text-sm text-gray-500">Review paper metadata and provide a documented decision.</p>
            </div>
            <a href="{{ route('submissions.campus-review-board') }}" class="text-sm font-semibold text-orange-700 hover:underline">Open review queue</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($recentBoardResearch as $paper)
            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <a href="{{ route('research.show', \App\Support\ResearchToken::encode($paper->id)) }}" class="font-semibold text-gray-900 hover:text-orange-700">{{ $paper->title }}</a>
                    <p class="mt-1 text-sm text-gray-500">{{ $paper->user->name ?? 'Unknown submitter' }} · {{ $paper->college->name ?? 'No college' }} · Submitted {{ ($paper->submitted_at ?? $paper->created_at)?->format('M d, Y h:i A') }}</p>
                </div>
                <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $paper->status_badge }}">{{ $paper->status_label }}</span>
            </div>
            @empty
            <p class="p-8 text-center text-sm text-gray-500">No papers are awaiting Campus Review Board review.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
