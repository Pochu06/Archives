<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminActionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
        'action',
        'method',
        'route_name',
        'path',
        'url',
        'ip_address',
        'user_agent',
        'request_data',
        'response_status',
    ];

    protected $casts = [
        'request_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}