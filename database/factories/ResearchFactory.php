<?php

namespace Database\Factories;

use App\Models\Research;
use App\Models\User;
use App\Models\College;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResearchFactory extends Factory
{
    protected $model = Research::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(rand(8, 15)),
            'abstract' => $this->faker->paragraphs(2, true),
            'introduction' => $this->faker->paragraphs(2, true),
            'methodology' => $this->faker->paragraphs(2, true),
            'results' => $this->faker->paragraphs(2, true),
            'discussion' => $this->faker->paragraphs(2, true),
            'keywords' => implode(', ', $this->faker->words(6)),
            'authors' => $this->faker->name() . ', ' . $this->faker->name(),
            'college_id' => College::inRandomOrder()->first()->id,
            'category_id' => Category::inRandomOrder()->first()->id,
            'user_id' => User::where('role', 'student')->inRandomOrder()->first()->id,
            'publication_year' => $this->faker->numberBetween(2018, 2025),
        ];
    }
}
