<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'college_id',
        'student_id', 'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    public function research()
    {
        return $this->hasMany(Research::class, 'user_id');
    }

    public function getRoleBadgeAttribute()
    {
        return match($this->role) {
            'super_admin' => 'bg-purple-100 text-purple-800',
            'admin' => 'bg-orange-100 text-orange-800',
            'student' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
