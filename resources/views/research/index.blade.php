@extends('layouts.app')
@section('title', 'Research Archive')
@section('page-title', 'Research Archive')
@section('page-subtitle', 'Browse archived IMRAD research papers')
@section('content')
<div class="space-y-6">
    <!-- Filters -->
    <div class="filters-panel bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($smartPresets as $preset)
            <a href="{{ route('research.index', $preset['query']) }}" class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 hover:bg-orange-100 transition">
                <i class="fas fa-bolt mr-1"></i> {{ $preset['label'] }}
            </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('research.index') }}" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-8 gap-4">
            <div class="md:col-span-2">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title, abstract, keywords, authors..."
                        class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-orange-500">
                </div>
            </div>
            <select name="college_id" class="border border-gray-200 rounded-xl px-3 py-3 text-sm focus:outline-none focus:border-orange-500">
                <option value="">All Colleges</option>
                @foreach($colleges as $college)
                <option value="{{ $college->id }}" {{ request('college_id') == $college->id ? 'selected' : '' }}>{{ $college->code }}</option>
                @endforeach
            </select>
            <select name="category_id" class="border border-gray-200 rounded-xl px-3 py-3 text-sm focus:outline-none focus:border-orange-500">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
                <select name="year" class="border border-gray-200 rounded-xl px-3 py-3 text-sm focus:outline-none focus:border-orange-500" title="Year from">
                    <option value="">Year From</option>
                    @for($filterYear = date('Y'); $filterYear >= 2020; $filterYear--)
                    <option value="{{ $filterYear }}" {{ (string) request('year') === (string) $filterYear ? 'selected' : '' }}>{{ $filterYear }}</option>
                    @endfor
                </select>
            <select name="year_to" class="border border-gray-200 rounded-xl px-3 py-3 text-sm focus:outline-none focus:border-orange-500" title="Year to">
                <option value="">Year To</option>
                @for($filterYear = date('Y'); $filterYear >= 2020; $filterYear--)
                <option value="{{ $filterYear }}" {{ (string) request('year_to') === (string) $filterYear ? 'selected' : '' }}>{{ $filterYear }}</option>
                @endfor
            </select>
            <select name="status" class="border border-gray-200 rounded-xl px-3 py-3 text-sm focus:outline-none focus:border-orange-500">
                <option value="">All Statuses</option>
                @foreach($statuses as $statusKey => $statusLabel)
                <option value="{{ $statusKey }}" {{ request('status') === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                @endforeach
            </select>
            <select name="sort" class="border border-gray-200 rounded-xl px-3 py-3 text-sm focus:outline-none focus:border-orange-500">
                <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Newest First</option>
                <option value="views" {{ request('sort') === 'views' ? 'selected' : '' }}>Most Viewed</option>
                <option value="downloads" {{ request('sort') === 'downloads' ? 'selected' : '' }}>Most Downloaded</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-orange-600 text-white rounded-xl py-3 text-sm font-semibold hover:bg-orange-700 transition">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('research.index') }}" class="bg-gray-100 text-gray-700 px-4 rounded-xl py-3 text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>

        <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 lg:grid-cols-2 gap-4">
            <form method="POST" action="{{ route('research.saved-searches.store') }}" class="flex flex-col sm:flex-row gap-2 sm:items-center">
                @csrf
                <input type="text" name="name" required maxlength="100" placeholder="Save current filters as..."
                    class="flex-1 border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-orange-500">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="college_id" value="{{ request('college_id') }}">
                <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                <input type="hidden" name="year" value="{{ request('year') }}">
                <input type="hidden" name="year_to" value="{{ request('year_to') }}">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-1"></i> Save Preset
                </button>
            </form>

            <div class="flex flex-wrap gap-2 lg:justify-end">
                @forelse($savedSearches as $saved)
                <div class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2 py-1">
                    <a href="{{ route('research.saved-searches.apply', $saved->id) }}" class="text-xs font-semibold text-gray-700 hover:text-orange-700 px-2">
                        <i class="fas fa-bookmark mr-1 text-orange-500"></i>{{ $saved->name }}
                    </a>
                    <form method="POST" action="{{ route('research.saved-searches.destroy', $saved->id) }}" onsubmit="return confirm('Delete this saved search?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-600 px-2" title="Delete preset">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                </div>
                @empty
                <p class="text-xs text-gray-500">No saved presets yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="no-print flex justify-between items-center">
        <p class="text-gray-600 text-sm">{{ $research->total() }} research paper(s) found</p>
        <div class="flex gap-2">
        <a href="{{ route('research.index', array_merge(request()->query(), ['print' => 1])) }}" target="_blank" class="bg-gray-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-gray-800 transition"><i class="fas fa-print mr-1"></i> Print</a>
        @if(in_array(session('user_role'), ['student', 'adviser', 'admin', 'super_admin']))
        <a href="{{ route('research.create') }}" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:from-orange-700 hover:to-orange-800 transition shadow">
            <i class="fas fa-plus mr-1"></i> Archive Paper
        </a>
        @endif
        </div>
    </div>

    @php
        $yearFrom = request('year');
        $yearTo = request('year_to');
        $isRange = $yearFrom && $yearTo && (string) $yearFrom !== (string) $yearTo;
        $showYearCol = $isRange;
        $showCollegeCol = ! request('college_id');
        $colCount = 4 + ($showYearCol ? 1 : 0) + ($showCollegeCol ? 1 : 0);
    @endphp

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase text-gray-500 border-b border-gray-200">
                    <th class="px-4 py-3 w-14">No.</th>
                    <th class="px-4 py-3">Title of Research</th>
                    <th class="px-4 py-3">Researchers / Authors</th>
                    <th class="px-4 py-3">Category</th>
                    @if($showYearCol)<th class="px-4 py-3">Year</th>@endif
                    @if($showCollegeCol)<th class="px-4 py-3">College</th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($research as $r)
                <tr>
                    <td class="px-4 py-3">{{ $loop->iteration + ($research->currentPage() - 1) * $research->perPage() }}</td>
                    <td class="px-4 py-3"><a href="{{ route('research.show', $r->id) }}" class="hover:text-orange-600 transition">{{ $r->title }}</a></td>
                    <td class="px-4 py-3">{{ $r->authors }}</td>
                    <td class="px-4 py-3">{{ $r->category->name ?? 'N/A' }}</td>
                    @if($showYearCol)<td class="px-4 py-3">{{ $r->publication_year }}</td>@endif
                    @if($showCollegeCol)<td class="px-4 py-3">{{ $r->college->code ?? 'N/A' }}</td>@endif
                </tr>
                @empty
                <tr><td colspan="{{ $colCount }}" class="px-4 py-10 text-center text-gray-500">No research papers match your current filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $research->withQueryString()->links() }}</div>
</div>
@endsection
