<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->json('thrusts')->nullable()->after('thrust');
        });

        Schema::table('research_drafts', function (Blueprint $table) {
            $table->json('thrusts')->nullable()->after('thrust');
        });
    }

    public function down(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->dropColumn('thrusts');
        });

        Schema::table('research_drafts', function (Blueprint $table) {
            $table->dropColumn('thrusts');
        });
    }
};