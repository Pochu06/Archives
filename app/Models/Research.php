<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Research extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'abstract', 'keywords', 'authors',
        'college_id', 'category_id', 'user_id', 'adviser_id',
        'status', 'publication_year', 'file_path', 'file_name',
        'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'publication_year' => 'integer',
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

    public function adviser()
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'approved' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
