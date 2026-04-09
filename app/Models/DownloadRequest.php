<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DownloadRequest extends Model
{
    protected $fillable = [
        'user_id', 'research_id', 'purpose',
        'status', 'reviewed_by', 'reviewed_at', 'rejection_reason',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function research()
    {
        return $this->belongsTo(Research::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
