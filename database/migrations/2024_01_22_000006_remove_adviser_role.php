<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop adviser_id from research table
        Schema::table('research', function (Blueprint $table) {
            $table->dropForeign(['adviser_id']);
            $table->dropColumn('adviser_id');
        });

        // Drop adviser_id from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('adviser_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('adviser_id')->nullable()->after('student_id');
        });

        Schema::table('research', function (Blueprint $table) {
            $table->foreignId('adviser_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }
};
