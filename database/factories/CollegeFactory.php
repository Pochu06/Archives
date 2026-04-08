<?php

namespace Database\Factories;

use App\Models\College;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollegeFactory extends Factory
{
    protected $model = College::class;

    public function definition()
    {
        return [
            'name' => 'College of ' . $this->faker->words(3, true),
            'code' => strtoupper($this->faker->lexify('???')),
            'description' => $this->faker->sentence(),
            'dean' => 'Dr. ' . $this->faker->name(),
            'contact_email' => $this->faker->safeEmail(),
            'active' => true,
        ];
    }
}
