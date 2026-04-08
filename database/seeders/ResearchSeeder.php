<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Research;
use App\Models\User;
use App\Models\College;
use App\Models\Category;

class ResearchSeeder extends Seeder
{
    public function run()
    {
        $students = User::where('role', 'student')->get();
        $colleges = College::all();
        $categories = Category::all();
        $advisers = User::where('role', 'adviser')->get();
        $admin = User::where('role', 'super_admin')->first();

        $titles = [
            ['title' => 'Artificial Intelligence in Educational Technology: Enhancing Student Learning Outcomes', 'college' => 'CICS'],
            ['title' => 'Machine Learning Approaches for Predicting Academic Performance in Philippine Universities', 'college' => 'CICS'],
            ['title' => 'Effectiveness of Online Learning Platforms During the COVID-19 Pandemic in Rural Areas', 'college' => 'CTED'],
            ['title' => 'Financial Literacy and Its Impact on Personal Investment Decisions Among College Students', 'college' => 'CBEA'],
            ['title' => 'Aquaculture Innovation: Sustainable Fish Farming Practices in Laguna de Bay', 'college' => 'CFAS'],
            ['title' => 'Renewable Energy Integration in Industrial Manufacturing Processes', 'college' => 'CIT'],
            ['title' => 'Community-Based Crime Prevention Strategies in Urban Barangays', 'college' => 'CCJE'],
            ['title' => 'Medicinal Plant Utilization Among Indigenous Communities in Mindanao', 'college' => 'CHM'],
            ['title' => 'Cybersecurity Threats and Countermeasures for Philippine Government Systems', 'college' => 'CICS'],
            ['title' => 'Indigenous Knowledge Integration in Modern Science Curriculum', 'college' => 'CTED'],
            ['title' => 'Microfinance and Entrepreneurship Development Among Rural Women', 'college' => 'CBEA'],
            ['title' => 'Marine Biodiversity Conservation in the Sulu Sea: A Comprehensive Study', 'college' => 'CFAS'],
            ['title' => 'Smart Manufacturing Technologies and Industry 4.0 Adoption in Philippine SMEs', 'college' => 'CIT'],
            ['title' => 'Restorative Justice Practices in the Philippine Juvenile Justice System', 'college' => 'CCJE'],
            ['title' => 'Traditional Filipino Herbal Medicine: Efficacy and Safety Analysis', 'college' => 'CHM'],
            ['title' => 'Blockchain Technology Applications in Academic Credential Verification', 'college' => 'CICS'],
            ['title' => 'Teacher Burnout and Its Impact on Student Performance in Public Schools', 'college' => 'CTED'],
            ['title' => 'E-Commerce Adoption and Business Performance Among MSMEs in Metro Manila', 'college' => 'CBEA'],
            ['title' => 'Mangrove Restoration and Its Role in Coastal Community Resilience', 'college' => 'CFAS'],
            ['title' => 'Ergonomics and Workplace Safety in Philippine Manufacturing Industries', 'college' => 'CIT'],
        ];

        $statuses = ['pending', 'pending', 'approved', 'approved', 'rejected'];
        $abstracts = [
            'This study investigates the impact and implementation of the subject matter in the Philippine educational context. Through mixed-methods research involving surveys, interviews, and data analysis, the researchers identified key factors affecting outcomes and propose evidence-based recommendations for policy and practice improvements.',
            'A comprehensive analysis was conducted examining various dimensions of the topic. The research employed quantitative and qualitative approaches to gather data from multiple stakeholders. Findings reveal significant correlations and patterns that contribute to the existing body of knowledge in this field.',
            'This research explores emerging trends and challenges in the identified domain. The study utilized a descriptive-analytical framework to evaluate current practices and identify areas for improvement. Results provide actionable insights for practitioners, administrators, and policymakers.',
        ];

        foreach ($titles as $i => $titleData) {
            $college = $colleges->where('code', $titleData['college'])->first();
            $student = $students->get($i % $students->count());
            $category = $categories->get($i % $categories->count());
            $adviser = $advisers->get($i % $advisers->count());
            $status = $statuses[$i % count($statuses)];

            Research::create([
                'title' => $titleData['title'],
                'abstract' => $abstracts[$i % count($abstracts)],
                'keywords' => 'research, education, Philippines, technology, innovation, study, analysis',
                'authors' => $student ? $student->name . ', et al.' : 'Unknown Author',
                'college_id' => $college ? $college->id : $colleges->first()->id,
                'category_id' => $category->id,
                'user_id' => $student ? $student->id : User::where('role', 'student')->first()->id,
                'adviser_id' => $adviser->id,
                'status' => $status,
                'publication_year' => rand(2020, 2024),
                'approved_by' => $status === 'approved' ? $admin->id : null,
                'approved_at' => $status === 'approved' ? now()->subDays(rand(1, 90)) : null,
                'rejection_reason' => $status === 'rejected' ? 'The submission requires significant revisions. Please address the methodology section and resubmit.' : null,
            ]);
        }
    }
}
