<?php

namespace Tests\Feature;

use App\Models\JournalIssue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JournalIssueManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('research', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('authors')->nullable();
            $table->unsignedBigInteger('college_id')->nullable();
            $table->string('status')->default('approved');
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamps();
        });
        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('admin_action_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('role', 20);
            $table->string('action');
            $table->string('method', 10);
            $table->string('route_name')->nullable();
            $table->string('path');
            $table->text('url');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_data')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->timestamps();
        });

        $migration = require base_path('database/migrations/2026_10_23_225856_create_journal_issues_table.php');
        $migration->up();
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('research');
        Schema::dropIfExists('journal_issue_events');
        Schema::dropIfExists('journal_issues');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('colleges');
        Schema::dropIfExists('admin_action_logs');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_only_rde_personnel_can_manage_journal_issues(): void
    {
        $this->withSession([
            'user_id' => 15,
            'user_role' => 'admin',
            'user_college_id' => 2,
        ])->get(route('journal-issues.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'Unauthorized.');

        $this->withSession([
            'user_id' => 10,
            'user_role' => 'admin',
            'user_college_id' => null,
        ])->get(route('journal-issues.index'))->assertOk();
    }

    public function test_rde_can_create_assign_finalize_and_reopen_an_issue(): void
    {
        $userId = DB::table('users')->insertGetId(['name' => 'RDE Reviewer']);
        $paperId = DB::table('research')->insertGetId([
            'title' => 'Published research',
            'authors' => 'A. Researcher',
            'status' => 'approved',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $unpublishedPaperId = DB::table('research')->insertGetId([
            'title' => 'Unpublished research',
            'authors' => 'B. Researcher',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $session = [
            'user_id' => $userId,
            'user_role' => 'admin',
            'user_college_id' => null,
        ];

        $this->withSession($session)->post(route('journal-issues.store'), [
            'journal_title' => 'CSU Research Journal',
            'volume_number' => 4,
            'issue_number' => 2,
            'publication_year' => 2026,
            'publication_date' => '2026-10-23',
        ])->assertRedirect();

        $issue = JournalIssue::firstOrFail();
        $this->assertSame(JournalIssue::STATUS_DRAFT, $issue->status);

        $this->withSession($session)->post(route('journal-issues.papers.update', $issue), [
            'research_ids' => [$unpublishedPaperId],
        ])->assertSessionHasErrors('research_ids');
        $this->assertDatabaseMissing('research', [
            'id' => $unpublishedPaperId,
            'journal_issue_id' => $issue->id,
        ]);

        $this->withSession($session)->post(route('journal-issues.papers.update', $issue), [
            'research_ids' => [$paperId],
        ])->assertRedirect();
        $this->assertDatabaseHas('research', [
            'id' => $paperId,
            'journal_issue_id' => $issue->id,
        ]);

        $this->withSession($session)->post(route('journal-issues.finalize', $issue), [
            'confirmation' => '1',
        ])->assertRedirect();
        $issue->refresh();
        $this->assertSame(JournalIssue::STATUS_FINALIZED, $issue->status);
        $this->assertSame($userId, $issue->finalized_by);
        $this->assertNotNull($issue->finalized_at);
        $this->assertDatabaseHas('journal_issue_events', [
            'journal_issue_id' => $issue->id,
            'action' => 'finalized',
            'actor_id' => $userId,
        ]);
        $this->withSession($session)->get(route('journal-issues.show', $issue))
            ->assertOk()
            ->assertSee('Papers in this issue')
            ->assertSee('Published research');

        $this->withSession($session)->put(route('journal-issues.update', $issue), [
            'journal_title' => 'Changed title',
            'volume_number' => 4,
            'issue_number' => 2,
            'publication_year' => 2026,
        ])->assertForbidden();

        $this->withSession($session)->post(route('journal-issues.reopen', $issue), [
            'revision_reason' => 'Correct the journal title.',
        ])->assertRedirect();
        $issue->refresh();
        $this->assertSame(JournalIssue::STATUS_DRAFT, $issue->status);
        $this->assertNull($issue->finalized_at);
        $this->assertDatabaseHas('journal_issue_events', [
            'journal_issue_id' => $issue->id,
            'action' => 'revision_started',
            'notes' => 'Correct the journal title.',
            'actor_id' => $userId,
        ]);
    }

    public function test_duplicate_issue_combinations_are_rejected(): void
    {
        $userId = DB::table('users')->insertGetId(['name' => 'RDE Reviewer']);
        $session = [
            'user_id' => $userId,
            'user_role' => 'super_admin',
            'user_college_id' => null,
        ];
        $payload = [
            'journal_title' => 'CSU Research Journal',
            'volume_number' => 1,
            'issue_number' => 1,
            'publication_year' => 2026,
        ];

        $this->withSession($session)->post(route('journal-issues.store'), $payload)->assertRedirect();
        $this->withSession($session)->post(route('journal-issues.store'), $payload)
            ->assertSessionHasErrors('issue_number');

        $this->assertDatabaseCount('journal_issues', 1);
    }
}
