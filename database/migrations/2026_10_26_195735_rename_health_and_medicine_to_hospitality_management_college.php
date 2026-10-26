<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('colleges')
            ->where('code', 'CHM')
            ->update([
                'name' => 'College of Hospitality Management',
                'description' => 'Advancing hospitality management, tourism, and service industry research.',
            ]);
    }

    public function down(): void
    {
        DB::table('colleges')
            ->where('code', 'CHM')
            ->where('name', 'College of Hospitality Management')
            ->update([
                'name' => 'College of Health and Medicine',
                'description' => 'Promoting health sciences, medical research, and community health programs.',
            ]);
    }
};
