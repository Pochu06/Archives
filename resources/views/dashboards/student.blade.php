@extends('layouts.app')
@section('title', 'Student Dashboard')
@section('page-title', 'Student Dashboard')
@section('page-subtitle', 'Welcome back, ' . session('user_name'))
@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
        <div class="bg-gradient-to-br from-orange-500 to-orange-700 rounded-2xl p-5 text-white shadow-lg">
            <i class="fas fa-archive text-2xl mb-3 opacity-80"></i>
            <p class="text-3xl font-extrabold">{{ $myResearch }}</p>
            <p class="text-orange-100 text-sm">My Archived Papers</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="p-6 border-b flex justify-between">
                <h3 class="font-bold text-gray-800">My Papers</h3>
                <a href="{{ route('research.index') }}" class="text-orange-600 text-sm font-semibold hover:underline">View All</a>
            </div>
            <div class="divide-y">
                @forelse($recentResearch as $r)
                <div class="p-4 hover:bg-orange-50/30">
                    <a href="{{ route('research.show', $r->id) }}" class="font-semibold text-gray-800 text-sm hover:text-orange-600 block truncate">{{ $r->title }}</a>
                    <p class="text-xs text-gray-500 mt-1">{{ $r->category->name ?? 'Uncategorized' }} &bull; {{ $r->publication_year }}</p>
                </div>
                @empty
                <p class="p-6 text-center text-gray-500">No papers archived yet. <a href="{{ route('research.create') }}" class="text-orange-600 font-semibold">Archive your first paper!</a></p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="p-6 border-b flex justify-between">
                <h3 class="font-bold text-gray-800">Browse Archive</h3>
                <a href="{{ route('research.index') }}" class="text-orange-600 text-sm font-semibold hover:underline">View All</a>
            </div>
            <div class="divide-y">
                @forelse($browseResearch as $r)
                <div class="p-4 hover:bg-orange-50/30">
                    <a href="{{ route('research.show', $r->id) }}" class="font-semibold text-gray-800 text-sm hover:text-orange-600 block truncate">{{ $r->title }}</a>
                    <p class="text-xs text-gray-500 mt-1">{{ $r->college->code ?? 'N/A' }} &bull; {{ $r->authors }}</p>
                </div>
                @empty
                <p class="p-6 text-center text-gray-500">No papers in the archive yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-2xl p-6 flex items-center justify-between">
        <div>
            <h3 class="font-bold text-orange-800 text-lg">Ready to archive your research?</h3>
            <p class="text-orange-700 text-sm">Upload your final IMRAD research paper to the archive.</p>
        </div>
        <a href="{{ route('research.create') }}" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-6 py-3 rounded-xl font-bold hover:from-orange-700 hover:to-orange-800 transition shadow flex-shrink-0">
            <i class="fas fa-plus mr-2"></i> Archive Paper
        </a>
    </div>
</div>
@endsection