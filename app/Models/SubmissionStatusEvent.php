<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionStatusEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'research_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'notes',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function research()
    {
        return $this->belongsTo(Research::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
