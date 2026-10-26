@extends('layouts.app')
@section('title', 'Campus Review Board Queue')
@section('page-title', 'Campus Review Board Queue')
@section('page-subtitle', 'Review each submitted paper before it proceeds to the RDE Office')
@section('content')
<div class="space-y-5">
    <form method="GET" class="flex flex-wrap items-center gap-3 rounded-2xl border border-gray-100 bg-white p-4">
        <label for="status" class="text-sm font-semibold text-gray-700">Submission status</label>
        <select id="status" name="status" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
            <option value="">Pending Campus Review Board</option>
            @foreach($allowedStatuses as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
        <button class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Filter</button>
    </form>

    <div class="space-y-4">
        @forelse($research as $paper)
        <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-gray-900">{{ $paper->title }}</h2>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $paper->status_badge }}">{{ $paper->status_label }}</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">{{ $paper->authors }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $paper->user->name ?? 'Unknown submitter' }} · {{ $paper->college->name ?? 'No college' }} · {{ $paper->category->name ?? 'Uncategorized' }} · {{ $paper->publication_year }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Submitted {{ ($paper->submitted_at ?? $paper->created_at)?->format('M d, Y h:i A') }}</p>
                    @if($paper->revision_notes)<p class="mt-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">{{ $paper->revision_notes }}</p>@endif
                    @if($paper->rejection_reason)<p class="mt-2 rounded-lg bg-red-50 p-3 text-sm text-red-900">{{ $paper->rejection_reason }}</p>@endif
                    <a href="{{ route('research.show', \App\Support\ResearchToken::encode($paper->id)) }}" class="mt-3 inline-flex text-sm font-semibold text-orange-700 hover:underline">
                        Review full metadata and history <i class="fas fa-arrow-up-right-from-square ml-1 mt-1 text-xs"></i>
                    </a>
                </div>

                @if($paper->status === \App\Models\Research::STATUS_PENDING_CAMPUS_REVIEW)
                <div class="grid w-full gap-3 lg:max-w-xl lg:grid-cols-3">
                    <form method="POST" action="{{ route('submissions.campus-review-board-approve', $paper->id) }}" class="space-y-2">
                        @csrf
                        <label class="block text-xs font-semibold text-gray-600" for="approve-comment-{{ $paper->id }}">Comment (optional)</label>
                        <textarea id="approve-comment-{{ $paper->id }}" name="comment" rows="2" maxlength="1000" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="Review comment"></textarea>
                        <button class="w-full rounded-lg bg-green-700 px-3 py-2 text-sm font-semibold text-white hover:bg-green-800">Approve for RDE</button>
                    </form>
                    <form method="POST" action="{{ route('submissions.campus-review-board-revision', $paper->id) }}" class="space-y-2">
                        @csrf
                        <label class="block text-xs font-semibold text-gray-600" for="revision-reason-{{ $paper->id }}">Revision reason</label>
                        <textarea id="revision-reason-{{ $paper->id }}" name="reason" rows="2" maxlength="1000" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="Explain required revisions"></textarea>
                        <button class="w-full rounded-lg bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-700">Request revision</button>
                    </form>
                    <form method="POST" action="{{ route('submissions.campus-review-board-reject', $paper->id) }}" class="space-y-2">
                        @csrf
                        <label class="block text-xs font-semibold text-gray-600" for="reject-reason-{{ $paper->id }}">Rejection reason</label>
                        <textarea id="reject-reason-{{ $paper->id }}" name="reason" rows="2" maxlength="1000" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="Explain the rejection"></textarea>
                        <button class="w-full rounded-lg bg-red-700 px-3 py-2 text-sm font-semibold text-white hover:bg-red-800">Reject</button>
                    </form>
                </div>
                @endif
            </div>
        </article>
        @empty
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center text-sm text-gray-500">
            No submissions match this status.
        </div>
        @endforelse
    </div>

    {{ $research->withQueryString()->links() }}
</div>
@endsection
