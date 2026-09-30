<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function research()
    {
        return $this->hasMany(Research::class);
    }

    public function getBadgeClassesAttribute(): string
    {
        return match (strtolower(trim($this->name))) {
            'thesis' => 'bg-indigo-100 text-indigo-700',
            'dissertation' => 'bg-violet-100 text-violet-700',
            'capstone project' => 'bg-blue-100 text-blue-700',
            'research paper' => 'bg-emerald-100 text-emerald-700',
            'case study' => 'bg-amber-100 text-amber-700',
            'feasibility study' => 'bg-cyan-100 text-cyan-700',
            'action research' => 'bg-rose-100 text-rose-700',
            'review article' => 'bg-teal-100 text-teal-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }
}
