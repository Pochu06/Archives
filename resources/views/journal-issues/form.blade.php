@extends('layouts.app')
@section('title', $journalIssue->exists ? 'Edit Journal Issue' : 'New Journal Issue')
@section('page-title', $journalIssue->exists ? 'Edit Draft Issue' : 'Create Journal Issue')
@section('page-subtitle', 'Enter the journal volume and issue details')

@section('content')
<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ $journalIssue->exists ? route('journal-issues.update', $journalIssue) : route('journal-issues.store') }}" class="space-y-5 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-8">
        @csrf
        @if($journalIssue->exists) @method('PUT') @endif

        @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="mb-1 font-semibold">Please correct the following:</p>
            <ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
        @endif

        <div>
            <label for="journal_title" class="mb-1 block text-sm font-semibold text-gray-700">Journal title</label>
            <input id="journal_title" name="journal_title" value="{{ old('journal_title', $journalIssue->journal_title) }}" maxlength="255" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-100">
            @error('journal_title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="volume_number" class="mb-1 block text-sm font-semibold text-gray-700">Volume number</label>
                <input id="volume_number" type="number" name="volume_number" min="1" max="65535" value="{{ old('volume_number', $journalIssue->volume_number) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                @error('volume_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="issue_number" class="mb-1 block text-sm font-semibold text-gray-700">Issue number</label>
                <input id="issue_number" type="number" name="issue_number" min="1" max="65535" value="{{ old('issue_number', $journalIssue->issue_number) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                @error('issue_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="publication_year" class="mb-1 block text-sm font-semibold text-gray-700">Publication year</label>
                <input id="publication_year" type="number" name="publication_year" min="1900" max="2100" value="{{ old('publication_year', $journalIssue->publication_year) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
                @error('publication_year')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="publication_date" class="mb-1 block text-sm font-semibold text-gray-700">Publication date <span class="font-normal text-gray-500">(required to finalize)</span></label>
            <input id="publication_date" type="date" name="publication_date" value="{{ old('publication_date', $journalIssue->publication_date?->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm">
            @error('publication_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-700">Save draft</button>
            <a href="{{ $journalIssue->exists ? route('journal-issues.show', $journalIssue) : route('journal-issues.index') }}" class="rounded-lg bg-gray-100 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-200">Cancel</a>
        </div>
    </form>
</div>
@endsection
