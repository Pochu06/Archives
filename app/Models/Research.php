<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
    public const STATUS_PENDING_RDE = 'pending_rde';
    public const STATUS_REVISION_COLLEGE = 'revision_college';
    public const STATUS_REVISION_RDE = 'revision_rde';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED_COLLEGE = 'rejected_college';
    public const STATUS_REJECTED_RDE = 'rejected_rde';

    protected $fillable = [
        'title', 'abstract', 'introduction', 'methodology',
        'results', 'discussion', 'references', 'conclusion',
        'recommendations', 'keywords', 'authors',
        'college_id', 'category_id', 'user_id',
        'publication_year', 'status', 'approved_by', 'approved_at', 'rejection_reason', 'revision_notes', 'revision_fields', 'revision_field_notes',
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'approved_at' => 'datetime',
        'revision_fields' => 'array',
        'revision_field_notes' => 'array',
    ];

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

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_COLLEGE => 'Pending College Approval',
            self::STATUS_PENDING_RDE => 'Pending RDE Approval',
            self::STATUS_REVISION_COLLEGE => 'For College Revision',
            self::STATUS_REVISION_RDE => 'For RDE Revision',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED_COLLEGE => 'Rejected by College',
            self::STATUS_REJECTED_RDE => 'Rejected by RDE',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_COLLEGE => 'bg-yellow-100 text-yellow-800',
            self::STATUS_PENDING_RDE => 'bg-blue-100 text-blue-800',
            self::STATUS_REVISION_COLLEGE, self::STATUS_REVISION_RDE => 'bg-orange-100 text-orange-800',
            self::STATUS_APPROVED => 'bg-green-100 text-green-800',
            self::STATUS_REJECTED_COLLEGE, self::STATUS_REJECTED_RDE => 'bg-red-100 text-red-800',
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
