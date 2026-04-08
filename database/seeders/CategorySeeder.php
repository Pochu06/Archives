<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Thesis', 'description' => 'Undergraduate and graduate thesis papers.'],
            ['name' => 'Dissertation', 'description' => 'Doctoral dissertation research papers.'],
            ['name' => 'Capstone Project', 'description' => 'Senior capstone and final year projects.'],
            ['name' => 'Research Paper', 'description' => 'General academic research papers and studies.'],
            ['name' => 'Case Study', 'description' => 'In-depth analysis of specific cases and phenomena.'],
            ['name' => 'Feasibility Study', 'description' => 'Studies assessing the viability of proposed projects.'],
            ['name' => 'Action Research', 'description' => 'Practitioner-based research aimed at improving practice.'],
            ['name' => 'Review Article', 'description' => 'Systematic reviews and meta-analyses of existing literature.'],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }
    }
}
