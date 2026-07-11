<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->string('thrust', 191)->nullable()->after('keywords');
        });

        Schema::table('research_drafts', function (Blueprint $table) {
            $table->string('thrust', 191)->nullable()->after('keywords');
        });
    }

    public function down(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->dropColumn('thrust');
        });

        Schema::table('research_drafts', function (Blueprint $table) {
            $table->dropColumn('thrust');
        });
    }
};