<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->string('table_design', 20)->default('classic')->after('publication_year');
        });

        Schema::table('research_drafts', function (Blueprint $table) {
            $table->string('table_design', 20)->nullable()->after('publication_year');
        });
    }

    public function down(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->dropColumn('table_design');
        });

        Schema::table('research_drafts', function (Blueprint $table) {
            $table->dropColumn('table_design');
        });
    }
};
