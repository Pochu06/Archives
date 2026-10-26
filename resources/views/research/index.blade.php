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
            <select name="academic_level" class="border border-gray-200 rounded-xl px-3 py-3 text-sm focus:outline-none focus:border-orange-500">
                <option value="">All Academic Levels</option>
                @foreach($academicLevels as $level => $label)
                <option value="{{ $level }}" {{ request('academic_level') === $level ? 'selected' : '' }}>{{ $label }}</option>
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
            @if(session('user_role') !== 'student')
            <select name="status" class="border border-gray-200 rounded-xl px-3 py-3 text-sm focus:outline-none focus:border-orange-500">
                <option value="">All Statuses</option>
                @foreach($statuses as $statusKey => $statusLabel)
                <option value="{{ $statusKey }}" {{ request('status') === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                @endforeach
            </select>
            @endif
            <select name="sort" class="border border-gray-200 rounded-xl px-3 py-3 text-sm focus:outline-none focus:border-orange-500">
                <option value="engagement" {{ request('sort', 'engagement') === 'engagement' ? 'selected' : '' }}>Most Viewed &amp; Downloaded</option>
                <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Newest First</option>
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
                <input type="hidden" name="academic_level" value="{{ request('academic_level') }}">
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
        @if(in_array(session('user_role'), ['admin', 'super_admin'], true))
        <a href="{{ route('research.index', array_merge(request()->query(), ['print' => 1])) }}" target="_blank" class="bg-gray-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-gray-800 transition"><i class="fas fa-print mr-1"></i> Print</a>
        @endif
        @if(in_array(session('user_role'), ['student', 'adviser', 'admin', 'super_admin']))
        <a href="{{ route('research.create') }}" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:from-orange-700 hover:to-orange-800 transition shadow">
            <i class="fas fa-plus mr-1"></i> Archive Paper
        </a>
        @endif
        </div>
    </div>

    @if(session('user_id'))
    <form method="POST" action="{{ route('research.bulk-download') }}" id="archiveBulkDownloadForm" class="no-print rounded-xl border border-blue-100 bg-blue-50 p-4">
        @csrf
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-sm text-blue-900">Select papers you are permitted to download and prepare their reports in one ZIP archive.</p>
            <div class="flex items-center gap-3">
                <span id="archiveBulkDownloadStatus" class="text-xs text-gray-600" aria-live="polite"></span>
                <button type="submit" disabled class="archive-bulk-download-button rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">
                    <i class="fas fa-file-zipper mr-1"></i> Download selected
                </button>
            </div>
        </div>
    @endif

    @if(session('user_role') === 'student')
    @if($research->count())
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
        @foreach($research as $r)
        <article class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            @if(session('user_id'))
            <label class="no-print mb-3 inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                <input type="checkbox" name="research_ids[]" value="{{ $r->id }}" class="archive-bulk-download-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                Include in ZIP
            </label>
            @endif
            <div class="mb-3 flex items-center justify-between gap-2">
                <span class="rounded px-2.5 py-1 text-xs font-bold {{ $r->category->badge_classes ?? 'bg-gray-100 text-gray-700' }}">{{ $r->category->name ?? 'Uncategorized' }}</span>
                <span class="text-xs text-gray-500">{{ ['BS' => 'BS', 'GS' => 'GS'][$r->academic_level] ?? 'Needs classification review' }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $r->publication_year }}</span>
            </div>

            <h2 class="mb-2 line-clamp-2 text-lg font-bold leading-snug text-gray-900 dark:text-white">{{ $r->title }}</h2>
            <p class="mb-3 line-clamp-3 text-sm text-gray-600 dark:text-gray-300">{{ $r->abstract }}</p>

            <div class="mb-4 space-y-1 text-xs text-gray-500 dark:text-gray-400">
                <p><span class="font-semibold text-gray-700 dark:text-gray-200">College:</span> {{ $r->college->code ?? 'N/A' }}</p>
                <p><span class="font-semibold text-gray-700 dark:text-gray-200">Authors:</span> {{ $r->authors }}</p>
                <p>
                    <span class="font-semibold text-gray-700 dark:text-gray-200">Archived by:</span>
                    @if($r->user)
                    <a href="{{ route('authors.show', $r->user->id) }}" class="text-orange-700 hover:underline dark:text-orange-400">{{ $r->user->name }}</a>
                    @else
                    N/A
                    @endif
                </p>
                <p class="flex items-center gap-3 pt-1">
                    <span><i class="fas fa-eye mr-1 text-blue-400"></i>{{ number_format($r->view_count) }} views</span>
                    <span><i class="fas fa-download mr-1 text-green-500"></i>{{ number_format($r->download_count) }} downloads</span>
                </p>
            </div>

            <a href="{{ route('research.show', \App\Support\ResearchToken::encode($r->id)) }}" class="mt-auto w-full rounded-lg bg-orange-600 py-2.5 text-center text-sm font-semibold text-white hover:bg-orange-700">
                View Research
            </a>
        </article>
        @endforeach
    </div>
    @else
    <div class="rounded-2xl border border-gray-100 bg-white px-4 py-10 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
        No research papers match your current filters.
    </div>
    @endif
    @else
    @php
        $yearFrom = request('year');
        $yearTo = request('year_to');
        $isRange = $yearFrom && $yearTo && (string) $yearFrom !== (string) $yearTo;
        $showYearCol = $isRange;
        $showCollegeCol = ! request('college_id');
        $showBulkCheckboxes = (bool) session('user_id');
        $colCount = 4 + ($showYearCol ? 1 : 0) + ($showCollegeCol ? 1 : 0) + ($showBulkCheckboxes ? 1 : 0);
    @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase text-gray-500 border-b border-gray-200">
                    <th class="px-4 py-3 w-14">No.</th>
                    @if($showBulkCheckboxes)<th class="no-print px-4 py-3">ZIP</th>@endif
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
                    @if($showBulkCheckboxes)
                    <td class="no-print px-4 py-3"><input type="checkbox" name="research_ids[]" value="{{ $r->id }}" aria-label="Include {{ $r->title }} in ZIP" class="archive-bulk-download-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"></td>
                    @endif
                    <td class="px-4 py-3"><a href="{{ route('research.show', \App\Support\ResearchToken::encode($r->id)) }}" class="hover:text-orange-600 transition">{{ $r->title }}</a></td>
                    <td class="px-4 py-3">{{ $r->authors }}</td>
                    <td class="px-4 py-3">{{ $r->category->name ?? 'N/A' }} · {{ ['BS' => 'BS', 'GS' => 'GS'][$r->academic_level] ?? 'Needs review' }}</td>
                    @if($showYearCol)<td class="px-4 py-3">{{ $r->publication_year }}</td>@endif
                    @if($showCollegeCol)<td class="px-4 py-3">{{ $r->college->code ?? 'N/A' }}</td>@endif
                </tr>
                @empty
                <tr><td colspan="{{ $colCount }}" class="px-4 py-10 text-center text-gray-500">No research papers match your current filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
    @if(session('user_id'))
    </form>
    <script>
        (() => {
            const form = document.getElementById('archiveBulkDownloadForm');
            const checkboxes = form.querySelectorAll('.archive-bulk-download-checkbox');
            const button = form.querySelector('.archive-bulk-download-button');
            const status = document.getElementById('archiveBulkDownloadStatus');
            const updateSelection = () => {
                const count = form.querySelectorAll('.archive-bulk-download-checkbox:checked').length;
                button.disabled = count === 0;
                status.textContent = count ? `${count} paper${count === 1 ? '' : 's'} selected` : 'Select at least one paper.';
            };
            checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
            form.addEventListener('submit', () => {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Preparing ZIP...';
                status.textContent = 'This may take a moment for larger selections.';
            });
            updateSelection();
        })();
    </script>
    @endif
    <div>{{ $research->withQueryString()->links() }}</div>
</div>
@endsection
