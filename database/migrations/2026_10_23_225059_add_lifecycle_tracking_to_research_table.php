<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('approved_at');
            $table->foreignId('submitted_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('college_reviewed_at')->nullable()->after('submitted_by');
            $table->foreignId('college_reviewed_by')->nullable()->after('college_reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('campus_review_board_reviewed_at')->nullable()->after('college_reviewed_by');
            $table->foreignId('campus_review_board_reviewed_by')->nullable()->after('campus_review_board_reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rde_reviewed_at')->nullable()->after('campus_review_board_reviewed_by');
            $table->foreignId('rde_reviewed_by')->nullable()->after('rde_reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable()->after('rde_reviewed_by');
            $table->foreignId('accepted_by')->nullable()->after('accepted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->after('accepted_by');
            $table->foreignId('published_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();

            $table->index('submitted_at');
            $table->index('college_reviewed_at');
            $table->index('campus_review_board_reviewed_at');
            $table->index('rde_reviewed_at');
            $table->index('accepted_at');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['college_reviewed_by']);
            $table->dropForeign(['campus_review_board_reviewed_by']);
            $table->dropForeign(['rde_reviewed_by']);
            $table->dropForeign(['accepted_by']);
            $table->dropForeign(['published_by']);
            $table->dropIndex(['submitted_at']);
            $table->dropIndex(['college_reviewed_at']);
            $table->dropIndex(['campus_review_board_reviewed_at']);
            $table->dropIndex(['rde_reviewed_at']);
            $table->dropIndex(['accepted_at']);
            $table->dropIndex(['published_at']);
            $table->dropColumn([
                'submitted_at',
                'submitted_by',
                'college_reviewed_at',
                'college_reviewed_by',
                'campus_review_board_reviewed_at',
                'campus_review_board_reviewed_by',
                'rde_reviewed_at',
                'rde_reviewed_by',
                'accepted_at',
                'accepted_by',
                'published_at',
                'published_by',
            ]);
        });
    }
};
