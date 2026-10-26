<?php

namespace App\Models;

use App\Services\ResearchCategoryClassification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class Research extends Model
{
    use HasFactory;

    public const REVISION_FIELD_TITLE = 'title';

    public const REVISION_FIELD_AUTHORS = 'authors';

    public const REVISION_FIELD_KEYWORDS = 'keywords';

    public const REVISION_FIELD_ABSTRACT = 'abstract';

    public const REVISION_FIELD_INTRODUCTION = 'introduction';

    public const REVISION_FIELD_METHODOLOGY = 'methodology';

    public const REVISION_FIELD_RESULTS = 'results';

    public const REVISION_FIELD_REFERENCES = 'references';

    public const REVISION_FIELD_CONCLUSION = 'conclusion';

    public const REVISION_FIELD_RECOMMENDATIONS = 'recommendations';

    public const STATUS_PENDING_COLLEGE = 'pending_college';

    public const STATUS_PENDING_CAMPUS_REVIEW = 'pending_campus_review';

    public const STATUS_PENDING_RDE = 'pending_rde';

    public const STATUS_REVISION_COLLEGE = 'revision_college';

    public const STATUS_REVISION_CAMPUS_REVIEW = 'revision_campus_review';

    public const STATUS_REVISION_RDE = 'revision_rde';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED_COLLEGE = 'rejected_college';

    public const STATUS_REJECTED_CAMPUS_REVIEW = 'rejected_campus_review';

    public const STATUS_REJECTED_RDE = 'rejected_rde';

    protected $fillable = [
        'title', 'abstract', 'introduction', 'methodology',
        'results', 'discussion', 'references', 'conclusion',
        'recommendations', 'keywords', 'authors',
        'thrust', 'thrusts', 'college_id', 'category_id', 'academic_level', 'academic_program', 'user_id', 'adviser_id',
        'publication_year', 'table_design', 'status', 'adviser_id', 'file_path', 'file_name', 'approved_by', 'approved_at', 'rejection_reason', 'revision_notes', 'revision_fields', 'revision_field_notes', 'view_count', 'download_count',
        'submitted_at', 'submitted_by', 'college_reviewed_at', 'college_reviewed_by',
        'campus_review_board_reviewed_at', 'campus_review_board_reviewed_by',
        'rde_reviewed_at', 'rde_reviewed_by', 'accepted_at', 'accepted_by',
        'published_at', 'published_by',
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
        'college_reviewed_at' => 'datetime',
        'campus_review_board_reviewed_at' => 'datetime',
        'rde_reviewed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'published_at' => 'datetime',
        'thrusts' => 'array',
        'revision_fields' => 'array',
        'revision_field_notes' => 'array',
        'view_count' => 'integer',
        'download_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Research $research) {
            if (($research->isDirty('academic_level') || $research->isDirty('category_id'))
                && $research->academic_level !== null
                && ! app(ResearchCategoryClassification::class)->allows(
                    $research->academic_level,
                    $research->category_id ? (int) $research->category_id : null
                )) {
                throw ValidationException::withMessages([
                    'category_id' => 'The selected research category does not match the academic level.',
                ]);
            }
        });
    }

    public static function revisionFieldOptions(): array
    {
        return [
            self::REVISION_FIELD_TITLE => 'Title',
            self::REVISION_FIELD_AUTHORS => 'Authors',
            self::REVISION_FIELD_KEYWORDS => 'Keywords',
            self::REVISION_FIELD_ABSTRACT => 'Abstract',
            self::REVISION_FIELD_INTRODUCTION => 'Introduction',
            self::REVISION_FIELD_METHODOLOGY => 'Methodology',
            self::REVISION_FIELD_RESULTS => 'Results and Discussion',
            self::REVISION_FIELD_REFERENCES => 'References',
            self::REVISION_FIELD_CONCLUSION => 'Conclusion',
            self::REVISION_FIELD_RECOMMENDATIONS => 'Recommendations',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function adviser()
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function collegeReviewer()
    {
        return $this->belongsTo(User::class, 'college_reviewed_by');
    }

    public function campusReviewBoardReviewer()
    {
        return $this->belongsTo(User::class, 'campus_review_board_reviewed_by');
    }

    public function rdeReviewer()
    {
        return $this->belongsTo(User::class, 'rde_reviewed_by');
    }

    public function acceptor()
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function journalIssue()
    {
        return $this->belongsTo(JournalIssue::class);
    }

    public function recordLifecycleStage(string $stage, int $actorId, ?Carbon $occurredAt = null): bool
    {
        $columns = match ($stage) {
            'submitted' => ['submitted_at', 'submitted_by'],
            'college_reviewed' => ['college_reviewed_at', 'college_reviewed_by'],
            'campus_review_board_reviewed' => ['campus_review_board_reviewed_at', 'campus_review_board_reviewed_by'],
            'rde_reviewed' => ['rde_reviewed_at', 'rde_reviewed_by'],
            'accepted' => ['accepted_at', 'accepted_by'],
            'published' => ['published_at', 'published_by'],
            default => throw new InvalidArgumentException("Unsupported research lifecycle stage: {$stage}"),
        };
        [$timestampColumn, $actorColumn] = $columns;
        $occurredAt ??= now();
        $updates = [
            $timestampColumn => $occurredAt,
            $actorColumn => $actorId,
            'updated_at' => now(),
        ];

        if ($stage === 'accepted') {
            if ($this->approved_at === null) {
                $updates['approved_at'] = $occurredAt;
            }
            if ($this->approved_by === null) {
                $updates['approved_by'] = $actorId;
            }
        }

        $query = static::query()->whereKey($this->getKey())->whereNull($timestampColumn);
        if ($stage === 'accepted' && $this->approved_at === null) {
            $query->whereNull('approved_at');
        }

        if ($query->update($updates) === 0) {
            return false;
        }

        $this->forceFill($updates);

        return true;
    }

    public function statusEvents()
    {
        return $this->hasMany(SubmissionStatusEvent::class)->orderBy('created_at', 'desc');
    }

    public function hasCurrentCampusReviewBoardApproval(): bool
    {
        $latestBoardDecision = $this->statusEvents()
            ->whereIn('action', [
                'approved_by_campus_review_board',
                'revision_requested_by_campus_review_board',
                'rejected_by_campus_review_board',
            ])
            ->reorder()
            ->orderByDesc('id')
            ->first();

        return $latestBoardDecision?->action === 'approved_by_campus_review_board'
            && $latestBoardDecision->to_status === self::STATUS_PENDING_RDE;
    }

    public function downloadRequests()
    {
        return $this->hasMany(DownloadRequest::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeArchiveRelevance($query, $collegeId = null, string $sort = 'engagement')
    {
        if ($collegeId) {
            $query->orderByRaw(
                'CASE WHEN college_id = ? THEN 0 ELSE 1 END',
                [$collegeId]
            );
        }

        if ($sort === 'views') {
            return $query->orderByDesc('view_count')
                ->orderByDesc('download_count')
                ->orderByDesc('created_at');
        }

        if ($sort === 'downloads') {
            return $query->orderByDesc('download_count')
                ->orderByDesc('view_count')
                ->orderByDesc('created_at');
        }

        if ($sort === 'latest') {
            return $query->orderByDesc('created_at');
        }

        return $query->orderByRaw(
            'COALESCE(view_count, 0) + COALESCE(download_count, 0) DESC'
        )->orderByDesc('created_at');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_COLLEGE => 'Pending College Approval',
            self::STATUS_PENDING_CAMPUS_REVIEW => 'Pending Campus Review Board',
            self::STATUS_PENDING_RDE => 'Pending RDE Approval',
            self::STATUS_REVISION_COLLEGE => 'For College Revision',
            self::STATUS_REVISION_CAMPUS_REVIEW => 'For Campus Review Board Revision',
            self::STATUS_REVISION_RDE => 'For RDE Revision',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED_COLLEGE => 'Rejected by College',
            self::STATUS_REJECTED_CAMPUS_REVIEW => 'Rejected by Campus Review Board',
            self::STATUS_REJECTED_RDE => 'Rejected by RDE',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_COLLEGE => 'bg-yellow-100 text-yellow-800',
            self::STATUS_PENDING_CAMPUS_REVIEW => 'bg-violet-100 text-violet-800',
            self::STATUS_PENDING_RDE => 'bg-blue-100 text-blue-800',
            self::STATUS_REVISION_COLLEGE, self::STATUS_REVISION_CAMPUS_REVIEW, self::STATUS_REVISION_RDE => 'bg-orange-100 text-orange-800',
            self::STATUS_APPROVED => 'bg-green-100 text-green-800',
            self::STATUS_REJECTED_COLLEGE, self::STATUS_REJECTED_CAMPUS_REVIEW, self::STATUS_REJECTED_RDE => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getRevisionFieldLabelsAttribute(): array
    {
        $options = self::revisionFieldOptions();

        return collect($this->revision_fields ?? [])
            ->map(fn (string $field) => $options[$field] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    public function getRevisionFieldNoteEntriesAttribute(): array
    {
        $options = self::revisionFieldOptions();
        $fieldNotes = $this->revision_field_notes ?? [];

        return collect($this->revision_fields ?? [])
            ->map(function (string $field) use ($options, $fieldNotes) {
                $note = trim((string) ($fieldNotes[$field] ?? ''));

                if ($note === '' || ! isset($options[$field])) {
                    return null;
                }

                return [
                    'field' => $field,
                    'label' => $options[$field],
                    'note' => $note,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
