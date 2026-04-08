<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class College extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'dean', 'contact_email', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function research()
    {
        return $this->hasMany(Research::class);
    }
}
