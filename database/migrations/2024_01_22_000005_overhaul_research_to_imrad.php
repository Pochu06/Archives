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

            // Drop foreign key before dropping column
            $table->dropForeign(['approved_by']);

            // Remove approval workflow fields
            $table->dropColumn(['status', 'approved_by', 'approved_at', 'rejection_reason']);
        });
    }

    public function down()
    {
        Schema::table('research', function (Blueprint $table) {
            $table->dropColumn(['introduction', 'methodology', 'results', 'discussion']);

            $table->string('status')->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
        });
    }
};
