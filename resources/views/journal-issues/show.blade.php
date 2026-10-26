@extends('layouts.app')
@section('title', 'Journal Issue')
@section('page-title', $journalIssue->journal_title)
@section('page-subtitle', 'Volume '.$journalIssue->volume_number.' · Issue '.$journalIssue->issue_number.' · '.$journalIssue->publication_year)

@section('content')
<div class="mx-auto max-w-6xl space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('journal-issues.index') }}" class="text-sm font-semibold text-orange-700 hover:underline"><i class="fas fa-arrow-left mr-1"></i> All journal issues</a>
        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $journalIssue->status === \App\Models\JournalIssue::STATUS_FINALIZED ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">{{ ucfirst($journalIssue->status) }}</span>
    </div>

    @if($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        <ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-7">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Volume / Issue</p><p class="mt-1 font-semibold text-gray-900">Vol. {{ $journalIssue->volume_number }}, No. {{ $journalIssue->issue_number }}</p></div>
            <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Publication year</p><p class="mt-1 font-semibold text-gray-900">{{ $journalIssue->publication_year }}</p></div>
            <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Publication date</p><p class="mt-1 font-semibold text-gray-900">{{ $journalIssue->publication_date?->format('M d, Y') ?? 'Not set' }}</p></div>
            <div><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Finalized</p><p class="mt-1 font-semibold text-gray-900">{{ $journalIssue->finalized_at?->format('M d, Y h:i A') ?? 'Not finalized' }}@if($journalIssue->finalizer) by {{ $journalIssue->finalizer->name }}@endif</p></div>
        </div>

        @if($journalIssue->status === \App\Models\JournalIssue::STATUS_DRAFT)
        <div class="mt-6 flex flex-wrap gap-3 border-t border-gray-100 pt-5">
            <a href="{{ route('journal-issues.edit', $journalIssue) }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200"><i class="fas fa-edit mr-1"></i> Edit details</a>
            <form method="POST" action="{{ route('journal-issues.finalize', $journalIssue) }}" onsubmit="return confirm('Finalize this journal issue? Its details and assigned papers will be locked until an authorized RDE user starts a controlled revision.');">
                @csrf
                <input type="hidden" name="confirmation" value="1">
                <button type="submit" class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800"><i class="fas fa-lock mr-1"></i> Finalize issue</button>
            </form>
        </div>
        @else
        <form method="POST" action="{{ route('journal-issues.reopen', $journalIssue) }}" class="mt-6 grid gap-3 border-t border-gray-100 pt-5 sm:grid-cols-[1fr_auto]" onsubmit="return confirm('Start a controlled revision? The issue will become a draft until it is finalized again.');">
            @csrf
            <div>
                <label for="revision_reason" class="mb-1 block text-sm font-semibold text-gray-700">Reason for controlled revision</label>
                <textarea id="revision_reason" name="revision_reason" rows="2" maxlength="2000" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Explain why this finalized issue needs revision"></textarea>
            </div>
            <button type="submit" class="self-end rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100">Reopen for revision</button>
        </form>
        @endif
    </section>

    <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-7">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-lg font-bold text-gray-900">Papers in this issue</h2><p class="text-sm text-gray-500">Only approved, published research can be assigned.</p></div>
            @if($journalIssue->status === \App\Models\JournalIssue::STATUS_DRAFT)
            <span class="text-sm font-medium text-gray-600">{{ $journalIssue->papers->count() }} assigned</span>
            @endif
        </div>

        @if($journalIssue->status === \App\Models\JournalIssue::STATUS_DRAFT)
        <form method="POST" action="{{ route('journal-issues.papers.update', $journalIssue) }}" class="space-y-4">
            @csrf
            <div class="max-h-96 space-y-2 overflow-y-auto rounded-xl border border-gray-200 p-3">
                @forelse($availablePapers as $paper)
                <label class="flex cursor-pointer items-start gap-3 rounded-lg p-3 hover:bg-gray-50">
                    <input type="checkbox" name="research_ids[]" value="{{ $paper->id }}" @checked(in_array($paper->id, old('research_ids', $journalIssue->papers->pluck('id')->all()))) class="mt-1 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-gray-900">{{ $paper->title }}</span>
                        <span class="mt-1 block text-xs text-gray-500">{{ $paper->authors }} · {{ $paper->college->name ?? 'Unknown college' }}</span>
                    </span>
                </label>
                @empty
                <p class="p-4 text-sm text-gray-500">No eligible published papers are available.</p>
                @endforelse
            </div>
            <button type="submit" class="rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700">Save paper assignments</button>
        </form>
        @else
        <div class="divide-y divide-gray-100">
            @forelse($journalIssue->papers as $paper)
            <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                <div>
                    <a href="{{ route('research.show', \App\Support\ResearchToken::encode($paper->id)) }}" class="font-semibold text-orange-700 hover:underline">{{ $paper->title }}</a>
                    <p class="mt-1 text-xs text-gray-500">{{ $paper->authors }} · {{ $paper->college->name ?? 'Unknown college' }}</p>
                </div>
                <span class="text-xs text-gray-500">Published {{ $paper->published_at?->format('M d, Y') }}</span>
            </div>
            @empty
            <p class="py-5 text-sm text-gray-500">No papers assigned.</p>
            @endforelse
        </div>
        @endif
    </section>

    <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-7">
        <h2 class="mb-4 text-lg font-bold text-gray-900">Issue audit history</h2>
        <div class="space-y-3">
            @forelse($journalIssue->events as $event)
            <div class="border-l-2 border-orange-200 pl-3">
                <p class="text-sm font-semibold text-gray-800">{{ ucfirst(str_replace('_', ' ', $event->action)) }}</p>
                @if($event->notes)<p class="mt-1 text-sm text-gray-600">{{ $event->notes }}</p>@endif
                <p class="mt-1 text-xs text-gray-400">{{ $event->actor?->name ?? 'Former user' }} · {{ $event->created_at?->format('M d, Y h:i A') }}</p>
            </div>
            @empty
            <p class="text-sm text-gray-500">No audit events recorded.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
