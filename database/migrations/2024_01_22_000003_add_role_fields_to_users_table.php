<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->after('email');
            $table->foreignId('college_id')->nullable()->after('role');
            $table->string('student_id', 50)->nullable()->after('college_id');
            $table->foreignId('adviser_id')->nullable()->after('student_id');
            $table->string('status')->default('active')->after('adviser_id');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'college_id', 'student_id', 'adviser_id', 'status']);
        });
    }
};
