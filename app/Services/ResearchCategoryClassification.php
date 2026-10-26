<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Research;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ResearchCategoryClassification
{
    private const CATEGORIES_BY_LEVEL = [
        'BS' => ['Thesis', 'Capstone Project'],
        'GS' => ['Thesis', 'Dissertation'],
    ];

    public function levels(): array
    {
        return [
            'BS' => "BS (Bachelor's Degree)",
            'GS' => 'GS (Graduate Studies)',
        ];
    }

    public function categories(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::CATEGORIES_BY_LEVEL))));
    }

    public function supportedCategories()
    {
        $names = array_map('strtolower', $this->categories());

        return Category::query()
            ->whereIn(DB::raw('LOWER(name)'), $names)
            ->orderBy('name')
            ->get();
    }

    public function allows(?string $academicLevel, ?int $categoryId): bool
    {
        if (! isset(self::CATEGORIES_BY_LEVEL[$academicLevel]) || $categoryId === null) {
            return false;
        }

        $category = Category::query()->find($categoryId);
        if (! $category) {
            return false;
        }

        return in_array(
            mb_strtolower(trim($category->name)),
            array_map('mb_strtolower', self::CATEGORIES_BY_LEVEL[$academicLevel]),
            true
        );
    }

    public function allowsAnyLevel(int $categoryId): bool
    {
        return $this->allows('BS', $categoryId) || $this->allows('GS', $categoryId);
    }

    public function reviewRequired(): Builder
    {
        return Research::query()->where(function (Builder $query) {
            $query->whereNull('academic_level')
                ->orWhereNotIn('academic_level', array_keys(self::CATEGORIES_BY_LEVEL))
                ->orWhereNull('category_id')
                ->orWhereDoesntHave('category')
                ->orWhere(function (Builder $query) {
                    $query->where('academic_level', 'BS')
                        ->whereHas('category', fn (Builder $category) => $category
                            ->whereNotIn(DB::raw('LOWER(name)'), ['thesis', 'capstone project']));
                })
                ->orWhere(function (Builder $query) {
                    $query->where('academic_level', 'GS')
                        ->whereHas('category', fn (Builder $category) => $category
                            ->whereNotIn(DB::raw('LOWER(name)'), ['thesis', 'dissertation']));
                });
        });
    }
}
