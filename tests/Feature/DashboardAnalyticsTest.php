<?php

namespace Tests\Feature;

use App\Models\Research;
use App\Services\DashboardAnalyticsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('role');
            $table->unsignedBigInteger('college_id')->nullable();
            $table->string('student_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('research', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('authors')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('adviser_id')->nullable();
            $table->unsignedBigInteger('college_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('academic_program')->nullable();
            $table->unsignedInteger('publication_year')->nullable();
            $table->string('status');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('college_reviewed_at')->nullable();
            $table->timestamp('campus_review_board_reviewed_at')->nullable();
            $table->unsignedBigInteger('journal_issue_id')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();
        });
        Schema::create('submission_status_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('research_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
        Schema::create('journal_issues', function (Blueprint $table) {
            $table->id();
            $table->string('journal_title');
            $table->unsignedInteger('volume_number');
            $table->unsignedInteger('issue_number');
            $table->unsignedSmallInteger('publication_year');
            $table->date('publication_date')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->timestamp('finalized_at')->nullable();
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
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('admin_action_logs');
        Schema::dropIfExists('journal_issues');
        Schema::dropIfExists('submission_status_events');
        Schema::dropIfExists('research');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('colleges');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_student_dashboard_only_shows_their_own_unpublished_research(): void
    {
        $collegeId = DB::table('colleges')->insertGetId(['name' => 'Science College', 'code' => 'SCI']);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Thesis']);
        $studentId = DB::table('users')->insertGetId(['name' => 'Student', 'role' => 'student', 'college_id' => $collegeId]);
        $otherStudentId = DB::table('users')->insertGetId(['name' => 'Other Student', 'role' => 'student', 'college_id' => $collegeId]);

        $this->insertPaper($studentId, $collegeId, $categoryId, 'My private submission', Research::STATUS_PENDING_COLLEGE);
        $this->insertPaper($otherStudentId, $collegeId, $categoryId, 'Another private submission', Research::STATUS_PENDING_COLLEGE);

        $this->withSession([
            'user_id' => $studentId,
            'user_name' => 'Student',
            'user_role' => 'student',
            'user_college_id' => $collegeId,
        ])->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My private submission')
            ->assertDontSee('Another private submission');
    }

    public function test_adviser_sees_only_assigned_papers_and_can_record_review(): void
    {
        $collegeId = DB::table('colleges')->insertGetId(['name' => 'Science College', 'code' => 'SCI']);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Thesis']);
        $adviserId = DB::table('users')->insertGetId(['name' => 'Adviser', 'role' => 'adviser', 'college_id' => $collegeId]);
        $otherAdviserId = DB::table('users')->insertGetId(['name' => 'Other Adviser', 'role' => 'adviser', 'college_id' => $collegeId]);
        $studentId = DB::table('users')->insertGetId(['name' => 'Student', 'role' => 'student', 'college_id' => $collegeId]);
        $assignedId = $this->insertPaper($studentId, $collegeId, $categoryId, 'Assigned paper', Research::STATUS_PENDING_COLLEGE, $adviserId);
        $this->insertPaper($studentId, $collegeId, $categoryId, 'Unassigned paper', Research::STATUS_PENDING_COLLEGE, $otherAdviserId);

        $session = [
            'user_id' => $adviserId,
            'user_name' => 'Adviser',
            'user_role' => 'adviser',
            'user_college_id' => $collegeId,
        ];

        $this->withSession($session)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Assigned paper')
            ->assertDontSee('Unassigned paper');

        $this->withSession($session)->post(route('adviser.research.review', $assignedId), [
            'decision' => 'return',
            'notes' => 'Please clarify the methodology.',
        ])->assertRedirect();

        $this->assertDatabaseHas('submission_status_events', [
            'research_id' => $assignedId,
            'actor_id' => $adviserId,
            'action' => 'adviser_review_returned',
            'notes' => 'Please clarify the methodology.',
        ]);
        $this->assertDatabaseHas('research', [
            'id' => $assignedId,
            'status' => Research::STATUS_PENDING_COLLEGE,
        ]);
    }

    public function test_college_reviewer_can_assign_only_an_adviser_from_the_papers_college(): void
    {
        $collegeId = DB::table('colleges')->insertGetId(['name' => 'Science College', 'code' => 'SCI']);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Thesis']);
        $reviewerId = DB::table('users')->insertGetId(['name' => 'College Reviewer', 'role' => 'admin', 'college_id' => $collegeId]);
        $adviserId = DB::table('users')->insertGetId(['name' => 'Adviser', 'role' => 'adviser', 'college_id' => $collegeId]);
        $studentId = DB::table('users')->insertGetId(['name' => 'Student', 'role' => 'student', 'college_id' => $collegeId]);
        $paperId = $this->insertPaper($studentId, $collegeId, $categoryId, 'For adviser assignment', Research::STATUS_PENDING_COLLEGE);

        $this->withSession([
            'user_id' => $reviewerId,
            'user_role' => 'admin',
            'user_college_id' => $collegeId,
        ])->post(route('submissions.assign-adviser', $paperId), ['adviser_id' => $adviserId])
            ->assertRedirect()
            ->assertSessionHas('success', 'Adviser assigned for review.');

        $this->assertDatabaseHas('research', ['id' => $paperId, 'adviser_id' => $adviserId]);
        $this->assertDatabaseHas('submission_status_events', [
            'research_id' => $paperId,
            'actor_id' => $reviewerId,
            'action' => 'adviser_assigned',
        ]);
    }

    public function test_csv_export_applies_filters_and_neutralizes_spreadsheet_formulas(): void
    {
        $collegeId = DB::table('colleges')->insertGetId(['name' => 'Science College', 'code' => 'SCI']);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Thesis']);
        $adminId = DB::table('users')->insertGetId(['name' => 'System Admin', 'role' => 'super_admin']);
        $this->insertPaper($adminId, $collegeId, $categoryId, '=2+2', Research::STATUS_APPROVED);
        $this->insertPaper($adminId, $collegeId, $categoryId, 'Excluded paper', Research::STATUS_PENDING_COLLEGE);

        $response = $this->withSession([
            'user_id' => $adminId,
            'user_role' => 'super_admin',
        ])->get(route('dashboard.analytics.export', ['status' => Research::STATUS_APPROVED]));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=2+2", $csv);
        $this->assertStringNotContainsString('Excluded paper', $csv);
    }

    public function test_rde_analytics_uses_database_records_for_periods_and_program_distribution(): void
    {
        $collegeId = DB::table('colleges')->insertGetId(['name' => 'Science College', 'code' => 'SCI']);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Thesis']);
        $adminId = DB::table('users')->insertGetId(['name' => 'System Admin', 'role' => 'super_admin']);
        $issueId = DB::table('journal_issues')->insertGetId([
            'journal_title' => 'Campus Journal',
            'volume_number' => 1,
            'issue_number' => 2,
            'publication_year' => 2026,
            'status' => 'finalized',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('research')->insert([
            'title' => 'Published by program',
            'authors' => 'Researcher',
            'user_id' => $adminId,
            'college_id' => $collegeId,
            'category_id' => $categoryId,
            'academic_program' => 'BS Information Technology',
            'publication_year' => 2026,
            'status' => Research::STATUS_APPROVED,
            'accepted_at' => now(),
            'published_at' => now(),
            'submitted_at' => now(),
            'journal_issue_id' => $issueId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $analytics = app(DashboardAnalyticsService::class)->forUser(
            $adminId,
            'super_admin',
            null,
            ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]
        );

        $this->assertSame(1, $analytics['total']);
        $this->assertSame(1, $analytics['acceptedInPeriod']);
        $this->assertSame(1, $analytics['publishedInPeriod']);
        $this->assertSame(1, $analytics['journalIssues']);
        $this->assertCount(1, $analytics['byProgram']);
        $this->assertSame('BS Information Technology', $analytics['byProgram']->first()->label);
        $this->assertSame(1, $analytics['byProgram']->first()->total);
    }

    private function insertPaper(
        int $userId,
        int $collegeId,
        int $categoryId,
        string $title,
        string $status,
        ?int $adviserId = null
    ): int {
        return DB::table('research')->insertGetId([
            'title' => $title,
            'authors' => 'Researcher',
            'user_id' => $userId,
            'adviser_id' => $adviserId,
            'college_id' => $collegeId,
            'category_id' => $categoryId,
            'publication_year' => 2026,
            'status' => $status,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
