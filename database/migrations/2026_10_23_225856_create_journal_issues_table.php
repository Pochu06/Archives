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
        Schema::create('journal_issues', function (Blueprint $table) {
            $table->id();
            $table->string('journal_title', 255);
            $table->unsignedInteger('volume_number');
            $table->unsignedInteger('issue_number');
            $table->unsignedSmallInteger('publication_year');
            $table->date('publication_date')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['journal_title', 'publication_year', 'volume_number', 'issue_number'],
                'journal_issues_unique_issue'
            );
        });

        Schema::create('journal_issue_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['journal_issue_id', 'created_at']);
        });

        Schema::table('research', function (Blueprint $table) {
            $table->foreignId('journal_issue_id')
                ->nullable()
                ->after('published_by')
                ->constrained('journal_issues')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_issue_id');
        });
        Schema::dropIfExists('journal_issue_events');
        Schema::dropIfExists('journal_issues');
    }
};
