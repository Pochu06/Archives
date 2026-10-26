<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->foreignId('adviser_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('academic_program')->nullable()->after('category_id');
            $table->index(['adviser_id', 'status'], 'research_adviser_status_index');
            $table->index(['academic_program', 'publication_year'], 'research_program_year_index');
        });

        if (Schema::hasTable('research_drafts')) {
            Schema::table('research_drafts', function (Blueprint $table) {
                $table->string('academic_program')->nullable()->after('category_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('research_drafts') && Schema::hasColumn('research_drafts', 'academic_program')) {
            Schema::table('research_drafts', function (Blueprint $table) {
                $table->dropColumn('academic_program');
            });
        }

        Schema::table('research', function (Blueprint $table) {
            $table->dropIndex('research_adviser_status_index');
            $table->dropIndex('research_program_year_index');
            $table->dropConstrainedForeignId('adviser_id');
            $table->dropColumn('academic_program');
        });
    }
};
