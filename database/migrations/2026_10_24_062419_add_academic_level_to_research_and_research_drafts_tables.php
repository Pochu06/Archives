<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->string('academic_level', 2)->nullable()->after('category_id')->index();
        });

        Schema::table('research_drafts', function (Blueprint $table) {
            $table->string('academic_level', 2)->nullable()->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('research_drafts', function (Blueprint $table) {
            $table->dropColumn('academic_level');
        });

        Schema::table('research', function (Blueprint $table) {
            $table->dropIndex(['academic_level']);
            $table->dropColumn('academic_level');
        });
    }
};
