<?php

namespace App\Services;

use App\Models\AdminActionLog;
use App\Models\Category;
use App\Models\College;
use App\Models\JournalIssue;
use App\Models\Research;
use App\Models\SubmissionStatusEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;

class DashboardAnalyticsService
{
    private const PENDING_STATUSES = [
        Research::STATUS_PENDING_COLLEGE,
        Research::STATUS_PENDING_CAMPUS_REVIEW,
        Research::STATUS_PENDING_RDE,
    ];

    private const REVISION_STATUSES = [
        Research::STATUS_REVISION_COLLEGE,
        Research::STATUS_REVISION_CAMPUS_REVIEW,
        Research::STATUS_REVISION_RDE,
    ];

    private const REJECTED_STATUSES = [
        Research::STATUS_REJECTED_COLLEGE,
        Research::STATUS_REJECTED_CAMPUS_REVIEW,
        Research::STATUS_REJECTED_RDE,
    ];

    public function forUser(int $userId, string $role, ?int $collegeId, array $filters): array
    {
        $baseQuery = $this->scopedQuery($userId, $role, $collegeId, $filters);
        $total = (clone $baseQuery)->count();

        $analytics = [
            'filters' => $filters,
            'total' => $total,
            'pending' => (clone $baseQuery)->whereIn('status', self::PENDING_STATUSES)->count(),
            'approved' => (clone $baseQuery)->where('status', Research::STATUS_APPROVED)->count(),
            'revisions' => (clone $baseQuery)->whereIn('status', self::REVISION_STATUSES)->count(),
            'rejected' => (clone $baseQuery)->whereIn('status', self::REJECTED_STATUSES)->count(),
            'published' => (clone $baseQuery)->where(function (Builder $query) {
                $query->whereNotNull('published_at')->orWhere('status', Research::STATUS_APPROVED);
            })->count(),
            'recentResearch' => (clone $baseQuery)->with(['college:id,name,code', 'category:id,name', 'user:id,name'])
                ->latest('created_at')->limit(12)->get(),
            'colleges' => $this->filterOptions($userId, $role, $collegeId, 'college'),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'academicLevels' => app(ResearchCategoryClassification::class)->levels(),
            'years' => (clone $baseQuery)->reorder()->whereNotNull('publication_year')
                ->distinct()->orderByDesc('publication_year')->pluck('publication_year'),
            'statusOptions' => [
                Research::STATUS_PENDING_COLLEGE,
                Research::STATUS_PENDING_CAMPUS_REVIEW,
                Research::STATUS_PENDING_RDE,
                Research::STATUS_REVISION_COLLEGE,
                Research::STATUS_REVISION_CAMPUS_REVIEW,
                Research::STATUS_REVISION_RDE,
                Research::STATUS_APPROVED,
                Research::STATUS_REJECTED_COLLEGE,
                Research::STATUS_REJECTED_CAMPUS_REVIEW,
                Research::STATUS_REJECTED_RDE,
            ],
        ];

        if ($role === 'student' || $role === 'faculty') {
            $analytics['myResearch'] = (clone $baseQuery)->with(['college:id,name,code', 'category:id,name'])
                ->latest('created_at')->limit(12)->get();
        }

        if ($role === 'adviser') {
            $analytics += $this->adviserMetrics($baseQuery, $userId);
        }

        if ($role === 'admin' && $collegeId) {
            $analytics += $this->collegeReviewerMetrics($baseQuery, $userId);
        }

        if ($role === 'campus_review_board') {
            $analytics += $this->boardMetrics($baseQuery);
        }

        if ($role === 'super_admin' || ($role === 'admin' && ! $collegeId)) {
            $analytics += $this->rdeMetrics($baseQuery, $filters);
        }

        if ($role === 'super_admin') {
            $analytics += $this->systemMetrics($filters);
        }

        return $analytics;
    }

    public function exportQuery(int $userId, string $role, ?int $collegeId, array $filters): Builder
    {
        return $this->scopedQuery($userId, $role, $collegeId, $filters)
            ->with(['college:id,name', 'category:id,name', 'user:id,name', 'adviser:id,name'])
            ->orderByDesc('created_at');
    }

    private function scopedQuery(
        int $userId,
        string $role,
        ?int $collegeId,
        array $filters,
        bool $includeSubmissionDateRange = true
    ): Builder {
        $query = Research::query();

        if (in_array($role, ['student', 'faculty'], true)) {
            $query->where('user_id', $userId);
        } elseif ($role === 'adviser') {
            $query->where('adviser_id', $userId);
        } elseif ($role === 'admin' && $collegeId) {
            $query->where('college_id', $collegeId);
        }

        return $query
            ->when($includeSubmissionDateRange ? ($filters['date_from'] ?? null) : null, fn (Builder $q, $date) => $q->whereDate('submitted_at', '>=', $date))
            ->when($includeSubmissionDateRange ? ($filters['date_to'] ?? null) : null, fn (Builder $q, $date) => $q->whereDate('submitted_at', '<=', $date))
            ->when($filters['college_id'] ?? null, fn (Builder $q, $id) => $q->where('college_id', $id))
            ->when($filters['category_id'] ?? null, fn (Builder $q, $id) => $q->where('category_id', $id))
            ->when($filters['academic_level'] ?? null, fn (Builder $q, $level) => $q->where('academic_level', $level))
            ->when($filters['year'] ?? null, fn (Builder $q, $year) => $q->where('publication_year', $year))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status));
    }

    private function filterOptions(int $userId, string $role, ?int $collegeId, string $type)
    {
        if ($type === 'college' && $role === 'admin' && $collegeId) {
            return College::query()->whereKey($collegeId)->orderBy('name')->get(['id', 'name']);
        }

        if (in_array($role, ['student', 'faculty'], true)) {
            return College::query()->whereIn('id', Research::query()->where('user_id', $userId)
                ->select('college_id')->distinct())->orderBy('name')->get(['id', 'name']);
        }

        if ($role === 'adviser') {
            return College::query()->whereIn('id', Research::query()->where('adviser_id', $userId)
                ->select('college_id')->distinct())->orderBy('name')->get(['id', 'name']);
        }

        return College::query()->orderBy('name')->get(['id', 'name']);
    }

    private function adviserMetrics(Builder $baseQuery, int $userId): array
    {
        $assignedCount = (clone $baseQuery)->count();
        $researchIds = (clone $baseQuery)->select('research.id');
        $latestDecisionIds = $this->latestAdviserDecisionIds($researchIds, $userId);
        $latestDecisions = SubmissionStatusEvent::query()->whereIn('submission_status_events.id', clone $latestDecisionIds);
        $completed = (clone $latestDecisions)->count();
        $approved = (clone $latestDecisions)->where('action', 'adviser_review_approved')->count();
        $returned = (clone $latestDecisions)->where('action', 'adviser_review_returned')->count();
        $pending = max(0, $assignedCount - $completed);
        $minutes = $this->averageEventTurnaround(clone $latestDecisions);

        $assigned = (clone $baseQuery)->with('user:id,name')
            ->orderByRaw('CASE WHEN status IN (?, ?, ?) THEN 0 ELSE 1 END', self::PENDING_STATUSES)
            ->latest('created_at')->limit(20)
            ->get(['id', 'title', 'user_id', 'submitted_at', 'created_at', 'status']);
        $dashboardLatestIds = $this->latestAdviserDecisionIds($assigned->pluck('id')->all(), $userId);
        $eventsByResearch = SubmissionStatusEvent::query()->whereIn('id', $dashboardLatestIds)
            ->with('actor:id,name')->get()->keyBy('research_id');

        foreach ($assigned as $paper) {
            $paper->setAttribute('latestAdviserReview', $eventsByResearch->get($paper->id));
        }

        return [
            'assigned' => $assignedCount,
            'reviewPending' => $pending,
            'reviewCompleted' => $completed,
            'reviewApproved' => $approved,
            'reviewReturned' => $returned,
            'averageReviewHours' => $minutes === null ? null : round($minutes / 60, 1),
            'reviewActivity' => (clone $latestDecisions)->with(['actor:id,name', 'research:id,title'])
                ->latest()->limit(10)->get(),
            'adviserPapers' => $assigned,
        ];
    }

    private function latestAdviserDecisionIds(Builder|array $researchIds, int $userId): Builder
    {
        return SubmissionStatusEvent::query()
            ->selectRaw('MAX(id)')
            ->where('actor_id', $userId)
            ->whereIn('action', ['adviser_review_approved', 'adviser_review_returned'])
            ->whereIn('research_id', $researchIds)
            ->groupBy('research_id');
    }

    private function collegeReviewerMetrics(Builder $baseQuery, int $userId): array
    {
        $ids = (clone $baseQuery)->select('research.id');
        $events = SubmissionStatusEvent::query()
            ->whereIn('research_id', $ids)
            ->where('actor_id', $userId)
            ->whereIn('action', [
                'approved_by_college', 'rejected_by_college', 'revision_requested_by_college',
                'bulk_approved_college', 'bulk_rejected_college',
            ]);
        $completed = (clone $events)->count();
        $pending = (clone $baseQuery)->where('status', Research::STATUS_PENDING_COLLEGE)->count();
        $times = (clone $baseQuery)->whereNotNull('college_reviewed_at')
            ->select(['submitted_at', 'created_at', 'college_reviewed_at']);

        return [
            'assigned' => $pending + $completed,
            'reviewPending' => $pending,
            'reviewCompleted' => $completed,
            'reviewApproved' => (clone $events)->whereIn('action', ['approved_by_college', 'bulk_approved_college'])->count(),
            'reviewReturned' => (clone $events)->where('action', 'revision_requested_by_college')->count(),
            'averageReviewHours' => $this->averageLifecycleHours($times, 'college_reviewed_at'),
        ];
    }

    private function boardMetrics(Builder $baseQuery): array
    {
        $ids = (clone $baseQuery)->select('research.id');
        $events = SubmissionStatusEvent::query()
            ->whereIn('research_id', $ids)
            ->whereIn('action', [
                'approved_by_campus_review_board',
                'revision_requested_by_campus_review_board',
                'rejected_by_campus_review_board',
            ]);
        $times = (clone $baseQuery)->whereNotNull('campus_review_board_reviewed_at')
            ->select(['submitted_at', 'created_at', 'campus_review_board_reviewed_at']);

        return [
            'reviewPending' => (clone $baseQuery)->where('status', Research::STATUS_PENDING_CAMPUS_REVIEW)->count(),
            'reviewCompleted' => (clone $events)->count(),
            'reviewReturned' => (clone $events)->where('action', 'revision_requested_by_campus_review_board')->count(),
            'reviewRejected' => (clone $events)->where('action', 'rejected_by_campus_review_board')->count(),
            'averageReviewHours' => $this->averageLifecycleHours($times, 'campus_review_board_reviewed_at'),
            'reviewActivity' => (clone $events)->with(['actor:id,name', 'research:id,title'])
                ->latest()->limit(10)->get(),
        ];
    }

    private function rdeMetrics(Builder $baseQuery, array $filters): array
    {
        $periodQuery = $this->scopedQuery(0, 'super_admin', null, $filters, false);
        $publishedQuery = clone $periodQuery;
        $publishedQuery->where(function (Builder $query) {
            $query->whereNotNull('published_at')->orWhere('status', Research::STATUS_APPROVED);
        });
        if (! empty($filters['date_from'])) {
            $publishedQuery->whereDate('published_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $publishedQuery->whereDate('published_at', '<=', $filters['date_to']);
        }

        $acceptedQuery = clone $periodQuery;
        $acceptedQuery->whereNotNull('accepted_at');
        if (! empty($filters['date_from'])) {
            $acceptedQuery->whereDate('accepted_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $acceptedQuery->whereDate('accepted_at', '<=', $filters['date_to']);
        }

        return [
            'acceptedInPeriod' => $acceptedQuery->count(),
            'publishedInPeriod' => $publishedQuery->count(),
            'pendingByStage' => [
                'College review' => (clone $baseQuery)->where('status', Research::STATUS_PENDING_COLLEGE)->count(),
                'Campus Review Board' => (clone $baseQuery)->where('status', Research::STATUS_PENDING_CAMPUS_REVIEW)->count(),
                'RDE review' => (clone $baseQuery)->where('status', Research::STATUS_PENDING_RDE)->count(),
            ],
            'journalIssues' => JournalIssue::query()->count(),
            'finalizedJournalIssues' => JournalIssue::query()->where('status', JournalIssue::STATUS_FINALIZED)->count(),
            'journalIssueSummary' => JournalIssue::query()->where('status', JournalIssue::STATUS_FINALIZED)
                ->withCount('papers')->orderByDesc('publication_year')->orderByDesc('volume_number')
                ->orderByDesc('issue_number')->limit(6)->get(),
            'byCollege' => $this->groupCounts(clone $baseQuery, 'college_id', College::class),
            'byCategory' => $this->groupCounts(clone $baseQuery, 'category_id', Category::class),
            'byYear' => (clone $baseQuery)->reorder()->selectRaw('publication_year as label, COUNT(*) as total')
                ->whereNotNull('publication_year')->groupBy('publication_year')->orderByDesc('publication_year')->limit(12)->get(),
            'byProgram' => (clone $baseQuery)->reorder()->selectRaw('COALESCE(academic_program, ?) as label, COUNT(*) as total', ['Not specified'])
                ->groupBy('academic_program')->orderByDesc('total')->limit(12)->get(),
        ];
    }

    private function groupCounts(Builder $query, string $column, string $modelClass)
    {
        $counts = $query->reorder()->selectRaw($column.' as group_id, COUNT(*) as total')
            ->groupBy($column)->orderByDesc('total')->limit(20)->get();
        $names = $modelClass::query()->whereIn('id', $counts->pluck('group_id')->filter())
            ->pluck('name', 'id');

        return $counts->map(fn ($row) => (object) [
            'label' => $names[$row->group_id] ?? 'Unassigned',
            'total' => $row->total,
        ]);
    }

    private function systemMetrics(array $filters): array
    {
        $backupDirectory = storage_path('app/backups');
        $backupFiles = File::isDirectory($backupDirectory) ? File::files($backupDirectory) : [];
        $latestBackup = collect($backupFiles)->sortByDesc(fn ($file) => $file->getMTime())->first();
        $totalBytes = disk_total_space(storage_path());
        $freeBytes = disk_free_space(storage_path());
        $activityQuery = AdminActionLog::query();
        if (! empty($filters['date_from'])) {
            $activityQuery->whereDate('created_at', '>=', $filters['date_from']);
        } elseif (empty($filters['date_to'])) {
            $activityQuery->whereDate('created_at', '>=', now()->subDays(30));
        }
        if (! empty($filters['date_to'])) {
            $activityQuery->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'totalUsers' => User::query()->count(),
            'activeUsers' => User::query()->where('status', 'active')->count(),
            'pendingUsers' => User::query()->where('status', 'pending')->count(),
            'recentActivity' => (clone $activityQuery)->with('user:id,name')
                ->latest()->limit(10)->get(),
            'activityCount' => (clone $activityQuery)->count(),
            'storageTotalGb' => $totalBytes === false ? null : round($totalBytes / 1024 ** 3, 1),
            'storageUsedPercent' => ($totalBytes === false || $freeBytes === false || $totalBytes <= 0)
                ? null
                : round((1 - ($freeBytes / $totalBytes)) * 100, 1),
            'backupStatus' => $latestBackup
                ? 'Latest backup '.Carbon::createFromTimestamp($latestBackup->getMTime())->format('M d, Y h:i A')
                : 'No backup file found in storage/app/backups',
            'phpVersion' => PHP_VERSION,
            'environment' => app()->environment(),
        ];
    }

    private function averageEventTurnaround(Builder $events): ?float
    {
        $researchTable = (new Research)->getTable();
        $eventsTable = (new SubmissionStatusEvent)->getTable();
        $sum = 0;
        $count = 0;

        $rows = (clone $events)
            ->join($researchTable, $researchTable.'.id', '=', $eventsTable.'.research_id')
            ->selectRaw($eventsTable.'.created_at as reviewed_at, COALESCE('.$researchTable.'.submitted_at, '.$researchTable.'.created_at) as submitted_at');

        foreach ($rows->cursor() as $row) {
            $submittedAt = Carbon::parse($row->submitted_at);
            $reviewedAt = Carbon::parse($row->reviewed_at);
            if ($reviewedAt->greaterThanOrEqualTo($submittedAt)) {
                $sum += $submittedAt->diffInMinutes($reviewedAt);
                $count++;
            }
        }

        return $count === 0 ? null : $sum / $count;
    }

    private function averageLifecycleHours(Builder $research, string $field): ?float
    {
        $sum = 0;
        $count = 0;
        foreach ((clone $research)->cursor() as $paper) {
            $submittedAt = $paper->submitted_at ?? $paper->created_at;
            if ($submittedAt && $paper->{$field} && $paper->{$field}->greaterThanOrEqualTo($submittedAt)) {
                $sum += $submittedAt->diffInMinutes($paper->{$field});
                $count++;
            }
        }

        return $count === 0 ? null : round($sum / $count / 60, 1);
    }
}
