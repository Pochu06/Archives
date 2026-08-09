<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research', function (Blueprint $table) {
            if (! Schema::hasColumn('research', 'file_path')) {
                $table->string('file_path')->nullable()->after('publication_year');
            }

            if (! Schema::hasColumn('research', 'file_name')) {
                $table->string('file_name')->nullable()->after('file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('research', function (Blueprint $table) {
            if (Schema::hasColumn('research', 'file_name')) {
                $table->dropColumn('file_name');
            }

            if (Schema::hasColumn('research', 'file_path')) {
                $table->dropColumn('file_path');
            }
        });
    }
};
