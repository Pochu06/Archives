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
            'abstract' => $this->faker->paragraphs(3, true),
            'keywords' => implode(', ', $this->faker->words(6)),
            'authors' => $this->faker->name() . ', ' . $this->faker->name(),
            'college_id' => College::inRandomOrder()->first()->id,
            'category_id' => Category::inRandomOrder()->first()->id,
            'user_id' => User::where('role', 'student')->inRandomOrder()->first()->id,
            'adviser_id' => User::where('role', 'adviser')->inRandomOrder()->first()->id,
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'publication_year' => $this->faker->numberBetween(2018, 2024),
        ];
    }
}
