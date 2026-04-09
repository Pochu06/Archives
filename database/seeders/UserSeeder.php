<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\College;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $colleges = College::all();

        // Super Admin
        User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@university.edu.ph',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'college_id' => $colleges->first()->id,
            'status' => 'active',
        ]);

        // College Admins (one per college)
        $adminNames = ['CICS Admin', 'CTED Admin', 'CBEA Admin', 'CFAS Admin', 'CIT Admin', 'CCJE Admin', 'CHM Admin', 'GS Admin'];
        foreach ($colleges as $index => $college) {
            User::create([
                'name' => $adminNames[$index] ?? $college->code . ' Admin',
                'email' => strtolower($college->code) . '.admin@university.edu.ph',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'college_id' => $college->id,
                'status' => 'active',
            ]);
        }

        // RDE Office (admin with access to all papers)
        User::create([
            'name' => 'RDE Office',
            'email' => 'rde@university.edu.ph',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'college_id' => null,
            'status' => 'active',
        ]);

        // Students
        $studentData = [
            ['name' => 'Juan Dela Cruz', 'college' => 'CICS', 'student_id' => '2021-00001'],
            ['name' => 'Maria Santos', 'college' => 'CICS', 'student_id' => '2021-00002'],
            ['name' => 'Carlos Reyes', 'college' => 'CTED', 'student_id' => '2021-00003'],
            ['name' => 'Ana Bautista', 'college' => 'CBEA', 'student_id' => '2021-00004'],
            ['name' => 'Luis Garcia', 'college' => 'CFAS', 'student_id' => '2021-00005'],
            ['name' => 'Rosa Mendoza', 'college' => 'CIT', 'student_id' => '2021-00006'],
            ['name' => 'Mark Torres', 'college' => 'CCJE', 'student_id' => '2021-00007'],
            ['name' => 'Luz Aquino', 'college' => 'CHM', 'student_id' => '2021-00008'],
            ['name' => 'Paolo Cruz', 'college' => 'CICS', 'student_id' => '2021-00009'],
            ['name' => 'Nina Villanueva', 'college' => 'CTED', 'student_id' => '2021-00010'],
        ];

        foreach ($studentData as $student) {
            $college = $colleges->where('code', $student['college'])->first();
            $slug = strtolower(str_replace(' ', '.', $student['name']));
            User::create([
                'name' => $student['name'],
                'email' => $slug . '@student.university.edu.ph',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'college_id' => $college ? $college->id : $colleges->first()->id,
                'student_id' => $student['student_id'],
                'status' => 'active',
            ]);
        }
    }
}
