<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Research extends Model
{
    use HasFactory;

    public const STATUS_PENDING_COLLEGE = 'pending_college';
    public const STATUS_PENDING_RDE = 'pending_rde';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED_COLLEGE = 'rejected_college';
    public const STATUS_REJECTED_RDE = 'rejected_rde';

    protected $fillable = [
        'title', 'abstract', 'introduction', 'methodology',
        'results', 'discussion', 'references', 'conclusion',
        'recommendations', 'keywords', 'authors',
        'college_id', 'category_id', 'user_id',
        'publication_year', 'status', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'approved_at' => 'datetime',
    ];

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
            self::STATUS_APPROVED => 'bg-green-100 text-green-800',
            self::STATUS_REJECTED_COLLEGE, self::STATUS_REJECTED_RDE => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
