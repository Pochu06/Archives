<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ResearchDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'abstract', 'introduction', 'methodology',
        'results', 'discussion', 'references', 'conclusion',
        'recommendations', 'keywords', 'authors',
        'college_id', 'category_id', 'publication_year', 'last_saved_at',
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'last_saved_at' => 'datetime',
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
}
