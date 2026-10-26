<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalIssue extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINALIZED = 'finalized';

    protected $fillable = [
        'journal_title',
        'volume_number',
        'issue_number',
        'publication_year',
        'publication_date',
        'status',
        'created_by',
        'finalized_by',
        'finalized_at',
    ];

    protected $casts = [
        'volume_number' => 'integer',
        'issue_number' => 'integer',
        'publication_year' => 'integer',
        'publication_date' => 'date',
        'finalized_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function papers(): HasMany
    {
        return $this->hasMany(Research::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(JournalIssueEvent::class)->orderBy('created_at');
    }
}
