@extends('layouts.app')
@section('title', 'Journal Issues')
@section('page-title', 'Journal Volumes & Issues')
@section('page-subtitle', 'Manage journal publication records and assigned research')

@section('content')
<div class="mx-auto max-w-6xl space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-600">Create draft issues, assign published research, then finalize when ready.</p>
        <a href="{{ route('journal-issues.create') }}" class="rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700">
            <i class="fas fa-plus mr-1"></i> New journal issue
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[680px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Journal</th>
                        <th class="px-5 py-3">Volume / Issue</th>
                        <th class="px-5 py-3">Year</th>
                        <th class="px-5 py-3">Publication date</th>
                        <th class="px-5 py-3">Papers</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($journalIssues as $issue)
                    <tr>
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $issue->journal_title }}</td>
                        <td class="px-5 py-4 text-gray-700">Vol. {{ $issue->volume_number }}, No. {{ $issue->issue_number }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $issue->publication_year }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $issue->publication_date?->format('M d, Y') ?? 'Not set' }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $issue->papers_count }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $issue->status === \App\Models\JournalIssue::STATUS_FINALIZED ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ ucfirst($issue->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('journal-issues.show', $issue) }}" class="font-semibold text-orange-700 hover:underline">Manage</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">No journal issues created yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($journalIssues->hasPages())
        <div class="border-t border-gray-100 px-5 py-4">{{ $journalIssues->links() }}</div>
        @endif
    </div>
</div>
@endsection
