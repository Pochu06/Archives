<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->text('conclusion')->nullable()->after('references');
            $table->text('recommendations')->nullable()->after('conclusion');
            $table->dropColumn(['file_path', 'file_name']);
        });
    }

    public function down(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->dropColumn(['conclusion', 'recommendations']);
        });
    }
};
