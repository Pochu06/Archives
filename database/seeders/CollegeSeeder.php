<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\College;

class CollegeSeeder extends Seeder
{
    public function run()
    {
        $colleges = [
            ['name' => 'College of Information and Computing Sciences', 'code' => 'CICS', 'description' => 'Advancing computing, information technology, and data science research.', 'dean' => 'Dr. Maria Santos', 'contact_email' => 'cics@university.edu.ph', 'active' => true],
            ['name' => 'College of Teacher Education', 'code' => 'CTED', 'description' => 'Developing educators and advancing educational research and pedagogy.', 'dean' => 'Dr. Jose Reyes', 'contact_email' => 'cted@university.edu.ph', 'active' => true],
            ['name' => 'College of Business, Economics and Accountancy', 'code' => 'CBEA', 'description' => 'Fostering business innovation, economic research, and accounting standards.', 'dean' => 'Dr. Ana Cruz', 'contact_email' => 'cbea@university.edu.ph', 'active' => true],
            ['name' => 'College of Fisheries and Aquatic Sciences', 'code' => 'CFAS', 'description' => 'Advancing aquatic science, marine research, and sustainable fisheries.', 'dean' => 'Dr. Pedro Lim', 'contact_email' => 'cfas@university.edu.ph', 'active' => true],
            ['name' => 'College of Industrial Technology', 'code' => 'CIT', 'description' => 'Developing technical skills and advancing industrial technology research.', 'dean' => 'Dr. Ricardo Bautista', 'contact_email' => 'cit@university.edu.ph', 'active' => true],
            ['name' => 'College of Criminal Justice Education', 'code' => 'CCJE', 'description' => 'Advancing criminology, law enforcement, and criminal justice research.', 'dean' => 'Dr. Elena Gomez', 'contact_email' => 'ccje@university.edu.ph', 'active' => true],
            ['name' => 'College of Health and Medicine', 'code' => 'CHM', 'description' => 'Promoting health sciences, medical research, and community health programs.', 'dean' => 'Dr. Roberto Dela Cruz', 'contact_email' => 'chm@university.edu.ph', 'active' => true],
        ];

        foreach ($colleges as $college) {
            College::create($college);
        }
    }
}
