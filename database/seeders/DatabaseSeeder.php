<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            CollegeSeeder::class,
            CategorySeeder::class,
            UserSeeder::class,
            ResearchSeeder::class,
        ]);
    }
}
