@php($role = session('user_role'))
<section class="space-y-5">
    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-gray-900">Analytics</h2>
                <p class="text-sm text-gray-500">Statistics reflect records you are permitted to access.</p>
            </div>
            <a href="{{ route('dashboard.analytics.export', request()->query()) }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
        </div>
        <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <label class="text-xs font-semibold text-gray-600">Submitted from
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </label>
            <label class="text-xs font-semibold text-gray-600">Submitted to
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </label>
            @if($analytics['colleges']->isNotEmpty())
            <label class="text-xs font-semibold text-gray-600">College
                <select name="college_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All colleges</option>
                    @foreach($analytics['colleges'] as $college)
                    <option value="{{ $college->id }}" @selected((string) request('college_id') === (string) $college->id)>{{ $college->name }}</option>
                    @endforeach
                </select>
            </label>
            @endif
            <label class="text-xs font-semibold text-gray-600">Category
                <select name="category_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All categories</option>
                    @foreach($analytics['categories'] as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs font-semibold text-gray-600">Academic level
                <select name="academic_level" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All academic levels</option>
                    @foreach($analytics['academicLevels'] as $level => $label)
                    <option value="{{ $level }}" @selected(request('academic_level') === $level)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs font-semibold text-gray-600">Publication year
                <select name="year" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All years</option>
                    @foreach($analytics['years'] as $year)
                    <option value="{{ $year }}" @selected((string) request('year') === (string) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs font-semibold text-gray-600">Status
                <select name="status" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All statuses</option>
                    @foreach($analytics['statusOptions'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-6">
                <button class="rounded-xl bg-orange-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-800">Apply filters</button>
                <a href="{{ route('dashboard') }}" class="rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Clear</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
        @foreach([
            ['Submitted papers', $analytics['total'], 'bg-blue-700'],
            ['Awaiting review', $analytics['pending'], 'bg-amber-600'],
            ['Approved', $analytics['approved'], 'bg-emerald-700'],
            ['For revision', $analytics['revisions'], 'bg-orange-700'],
            ['Rejected', $analytics['rejected'], 'bg-red-700'],
            ['Published', $analytics['published'], 'bg-violet-700'],
        ] as [$label, $value, $color])
        <div class="rounded-2xl {{ $color }} p-4 text-white shadow-sm">
            <p class="text-2xl font-extrabold">{{ number_format($value) }}</p>
            <p class="mt-1 text-sm text-white/85">{{ $label }}</p>
        </div>
        @endforeach
    </div>

    @if(in_array($role, ['student', 'faculty'], true))
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="border-b border-gray-100 p-5">
            <h3 class="font-bold text-gray-900">Submission history and status</h3>
            <p class="mt-1 text-sm text-gray-500">Your own papers only. Open a paper to see its full progress history.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr><th class="px-5 py-3">Research</th><th class="px-5 py-3">Submitted</th><th class="px-5 py-3">Academic level / category</th><th class="px-5 py-3">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($analytics['myResearch'] as $paper)
                    <tr>
                        <td class="px-5 py-3"><a class="font-semibold text-orange-700 hover:underline" href="{{ route('research.show', \App\Support\ResearchToken::encode($paper->id)) }}">{{ $paper->title }}</a></td>
                        <td class="px-5 py-3 text-gray-600">{{ ($paper->submitted_at ?? $paper->created_at)?->format('M d, Y h:i A') }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $analytics['academicLevels'][$paper->academic_level] ?? 'Needs review' }} / {{ $paper->category?->name ?? 'Uncategorized' }}</td>
                        <td class="px-5 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $paper->status_badge }}">{{ $paper->status_label }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-gray-500">No submissions match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(in_array($role, ['adviser', 'campus_review_board'], true) || ($role === 'admin' && session('user_college_id')))
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
        @foreach([
            ['Assigned for review', $analytics['assigned'] ?? $analytics['reviewPending']],
            ['Pending reviews', $analytics['reviewPending']],
            ['Completed reviews', $analytics['reviewCompleted']],
            ['Approved', $analytics['reviewApproved'] ?? 0],
            ['Returned / rejected', ($analytics['reviewReturned'] ?? 0) + ($analytics['reviewRejected'] ?? 0)],
        ] as [$label, $value])
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <p class="text-2xl font-extrabold text-gray-900">{{ number_format($value) }}</p>
            <p class="mt-1 text-xs font-medium text-gray-500">{{ $label }}</p>
        </div>
        @endforeach
    </div>
    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <h3 class="font-bold text-gray-900">Review turnaround</h3>
        <p class="mt-2 text-sm text-gray-600">
            @if(($analytics['averageReviewHours'] ?? null) !== null)
                Average time from submission to review: <strong>{{ number_format($analytics['averageReviewHours'], 1) }} hours</strong>.
            @else
                Turnaround data is not available until at least one review has both submission and decision timestamps.
            @endif
        </p>
    </div>
    @endif

    @if($role === 'adviser')
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="border-b border-gray-100 p-5">
            <h3 class="font-bold text-gray-900">Assigned submissions</h3>
            <p class="mt-1 text-sm text-gray-500">Your recommendation is recorded in the audit history and does not bypass college, Board, or RDE approval.</p>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($analytics['adviserPapers'] as $paper)
            <div class="space-y-3 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <a href="{{ route('research.show', \App\Support\ResearchToken::encode($paper->id)) }}" class="font-semibold text-gray-900 hover:text-orange-700">{{ $paper->title }}</a>
                        <p class="mt-1 text-xs text-gray-500">{{ $paper->user?->name ?? 'Submitter' }} · Submitted {{ ($paper->submitted_at ?? $paper->created_at)?->format('M d, Y h:i A') }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $paper->status_badge }}">{{ $paper->status_label }}</span>
                </div>
                @if(!$paper->latestAdviserReview)
                <form method="POST" action="{{ route('adviser.research.review', $paper->id) }}" class="grid grid-cols-1 gap-2 sm:grid-cols-4">
                    @csrf
                    <select name="decision" required class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="approve">Recommend approval</option>
                        <option value="return">Return with feedback</option>
                    </select>
                    <input name="notes" required maxlength="1000" placeholder="Review comments (required)" class="rounded-lg border border-gray-300 px-3 py-2 text-sm sm:col-span-2">
                    <button class="rounded-lg bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">Record review</button>
                </form>
                @else
                <p class="text-sm text-gray-600">Latest recommendation: <strong>{{ str_replace('adviser_review_', '', str_replace('_', ' ', $paper->latestAdviserReview->action)) }}</strong> · {{ $paper->latestAdviserReview->created_at->format('M d, Y h:i A') }}</p>
                @endif
            </div>
            @empty
            <p class="p-8 text-center text-sm text-gray-500">No assigned submissions match these filters.</p>
            @endforelse
        </div>
    </div>
    @endif

    @if(isset($analytics['pendingByStage']))
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <h3 class="mb-4 font-bold text-gray-900">Pending workflow by stage</h3>
            <div class="space-y-4">
                @foreach($analytics['pendingByStage'] as $stage => $count)
                <div>
                    <div class="mb-1 flex justify-between gap-3 text-sm"><span class="text-gray-600">{{ $stage }}</span><strong>{{ number_format($count) }}</strong></div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-orange-600" style="width: {{ $analytics['total'] ? min(100, $count / $analytics['total'] * 100) : 0 }}%"></div></div>
                </div>
                @endforeach
            </div>
            <p class="mt-5 text-sm text-gray-600">Accepted in period: <strong>{{ number_format($analytics['acceptedInPeriod']) }}</strong> · Published in period: <strong>{{ number_format($analytics['publishedInPeriod']) }}</strong></p>
            <p class="mt-1 text-sm text-gray-600">Journal issues: <strong>{{ $analytics['journalIssues'] }}</strong> · Finalized: <strong>{{ $analytics['finalizedJournalIssues'] }}</strong></p>
            @if($analytics['journalIssueSummary']->isNotEmpty())
            <div class="mt-4 space-y-2 border-t border-gray-100 pt-4">
                <h4 class="text-xs font-bold uppercase tracking-wide text-gray-500">Recent finalized journal issues</h4>
                @foreach($analytics['journalIssueSummary'] as $issue)
                <p class="text-sm text-gray-700">{{ $issue->journal_title }} · Vol. {{ $issue->volume_number }}, Issue {{ $issue->issue_number }} ({{ $issue->publication_year }}) — {{ $issue->papers_count }} papers</p>
                @endforeach
            </div>
            @endif
        </div>
        @foreach([
            'By college' => $analytics['byCollege'],
            'By academic program' => $analytics['byProgram'],
            'By category' => $analytics['byCategory'],
            'By publication year' => $analytics['byYear'],
        ] as $chartTitle => $items)
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <h3 class="mb-4 font-bold text-gray-900">{{ $chartTitle }}</h3>
            <div class="space-y-3">
                @php($maxCount = max(1, (int) ($items->max('total') ?? 0)))
                @forelse($items as $item)
                <div>
                    <div class="mb-1 flex justify-between gap-3 text-sm"><span class="truncate text-gray-600">{{ $item->label }}</span><strong>{{ number_format($item->total) }}</strong></div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-blue-600" style="width: {{ min(100, $item->total / $maxCount * 100) }}%"></div></div>
                </div>
                @empty
                <p class="text-sm text-gray-500">No records available.</p>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @if($role === 'super_admin')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Registered users', $analytics['totalUsers']],
            ['Active accounts', $analytics['activeUsers']],
            ['Pending registrations', $analytics['pendingUsers']],
            ['System actions in 30 days', $analytics['activityCount']],
        ] as [$label, $value])
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <p class="text-2xl font-extrabold text-gray-900">{{ number_format($value) }}</p>
            <p class="mt-1 text-sm text-gray-500">{{ $label }}</p>
        </div>
        @endforeach
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <p class="text-2xl font-extrabold text-gray-900">{{ $analytics['storageUsedPercent'] === null ? 'Unavailable' : $analytics['storageUsedPercent'].'%' }}</p>
            <p class="mt-1 text-sm text-gray-500">Host disk utilization{{ $analytics['storageTotalGb'] ? ' · '.$analytics['storageTotalGb'].' GB total' : '' }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:col-span-2">
            <p class="font-bold text-gray-900">Backup status</p>
            <p class="mt-1 text-sm text-gray-600">{{ $analytics['backupStatus'] }}</p>
            <p class="mt-2 text-xs text-gray-500">Status reflects files found in the configured local backup directory only.</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <p class="font-bold text-gray-900">Runtime</p>
            <p class="mt-1 text-sm text-gray-600">PHP {{ $analytics['phpVersion'] }} · {{ ucfirst($analytics['environment']) }}</p>
        </div>
    </div>
    @if($analytics['recentActivity']->isNotEmpty())
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="border-b border-gray-100 p-5"><h3 class="font-bold text-gray-900">Recent system activity</h3></div>
        <div class="divide-y divide-gray-100">
            @foreach($analytics['recentActivity'] as $activity)
            <div class="flex flex-wrap justify-between gap-2 p-4 text-sm">
                <span class="text-gray-700">{{ $activity->user?->name ?? 'Deleted user' }} · {{ $activity->action }} <span class="text-gray-500">({{ $activity->route_name ?? $activity->path }})</span></span>
                <time class="text-xs text-gray-500">{{ $activity->created_at->format('M d, Y h:i A') }}</time>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endif
</section>
