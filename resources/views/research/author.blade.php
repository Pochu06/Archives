@extends('layouts.app')

@section('title', $author->name . ' | Author Profile')
@section('page-title', 'Author Profile')
@section('page-subtitle', 'Submitted research by ' . $author->name)

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ session('user_id') ? route('research.index') : route('research.public') }}" class="text-orange-600 hover:underline text-sm font-semibold">
            <i class="fas fa-arrow-left mr-1"></i> Back to Archive
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-orange-100 text-orange-700 font-bold flex items-center justify-center text-xl">
                    {{ strtoupper(substr($author->name, 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $author->name }}</h1>
                    <p class="text-sm text-gray-500">
                        {{ $author->college->name ?? 'No college assigned' }}
                    </p>
                </div>
            </div>
            <div class="text-left sm:text-right">
                <p class="text-xs uppercase tracking-wider text-gray-400 font-semibold">Visible Submissions</p>
                <p class="text-2xl font-bold text-gray-900">{{ $visibleResearchCount }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <form method="GET" action="{{ route('authors.show', $author->id) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search title, abstract, keywords, authors"
                class="md:col-span-3 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-500"
            >

            <select name="category_id" class="border border-gray-200 rounded-xl px-3 py-3 text-sm focus:outline-none focus:border-orange-500">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-orange-600 text-white rounded-xl py-3 text-sm font-semibold hover:bg-orange-700 transition">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('authors.show', $author->id) }}" class="bg-gray-100 text-gray-700 px-4 rounded-xl py-3 text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($research as $item)
        <article class="bg-white border border-gray-200 rounded-2xl p-5 flex flex-col">
            <div class="flex items-center justify-between gap-2 mb-3">
                <span class="text-xs font-bold bg-orange-100 text-orange-700 px-2.5 py-1 rounded">{{ $item->category->name ?? 'Uncategorized' }}</span>
                <span class="text-xs text-gray-500">{{ $item->publication_year }}</span>
            </div>

            <h2 class="text-base font-bold text-gray-900 leading-snug mb-2 line-clamp-2">{{ $item->title }}</h2>
            <p class="text-sm text-gray-600 mb-3 line-clamp-3">{{ $item->abstract }}</p>

            <div class="text-xs text-gray-500 space-y-1 mb-4">
                <p><span class="font-semibold text-gray-700">College:</span> {{ $item->college->code ?? 'N/A' }}</p>
                <p><span class="font-semibold text-gray-700">Authors:</span> {{ $item->authors }}</p>
                @if(session('user_id'))
                <p>
                    <span class="inline-flex text-[11px] px-2 py-0.5 rounded-full font-semibold {{ $item->status_badge }}">{{ $item->status_label }}</span>
                </p>
                @endif
            </div>

            <a href="{{ session('user_id') ? route('research.show', $item->id) : route('research.public-show', $item->id) }}" class="mt-auto w-full text-center bg-orange-600 text-white text-sm font-semibold py-2.5 rounded-lg hover:bg-orange-700">
                View Research
            </a>
        </article>
        @empty
        <div class="md:col-span-2 xl:col-span-3 bg-white border border-gray-200 rounded-2xl p-12 text-center">
            <i class="fas fa-folder-open text-4xl text-gray-300 mb-3"></i>
            <h3 class="text-lg font-bold text-gray-800">No research papers found</h3>
            <p class="text-gray-500 mt-1">Try adjusting your search or filters.</p>
        </div>
        @endforelse
    </div>

    @if($research->hasPages())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        {{ $research->links() }}
    </div>
    @endif
</div>
@endsection