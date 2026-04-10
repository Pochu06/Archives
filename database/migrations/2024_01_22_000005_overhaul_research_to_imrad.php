<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('research', function (Blueprint $table) {
            // Add IMRAD metadata fields
            $table->text('introduction')->nullable()->after('abstract');
            $table->text('methodology')->nullable()->after('introduction');
            $table->text('results')->nullable()->after('methodology');
            $table->text('discussion')->nullable()->after('results');
        });
    }

    public function down()
    {
        Schema::table('research', function (Blueprint $table) {
            $table->dropColumn(['introduction', 'methodology', 'results', 'discussion']);
        });
    }
};
