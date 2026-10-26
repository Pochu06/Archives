<?php

namespace Tests\Feature;

use App\Models\Research;
use App\Support\ResearchToken;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CampusReviewBoardWorkflowTest extends TestCase
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
            $table->string('status')->default('active');
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
            $table->longText('abstract')->nullable();
            $table->longText('introduction')->nullable();
            $table->longText('methodology')->nullable();
            $table->longText('results')->nullable();
            $table->longText('discussion')->nullable();
            $table->longText('references')->nullable();
            $table->longText('conclusion')->nullable();
            $table->longText('recommendations')->nullable();
            $table->string('keywords')->nullable();
            $table->string('authors')->nullable();
            $table->string('thrust')->nullable();
            $table->json('thrusts')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('college_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('academic_level')->nullable();
            $table->string('academic_program')->nullable();
            $table->unsignedInteger('publication_year')->nullable();
            $table->string('table_design')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('college_reviewed_at')->nullable();
            $table->unsignedBigInteger('college_reviewed_by')->nullable();
            $table->timestamp('campus_review_board_reviewed_at')->nullable();
            $table->unsignedBigInteger('campus_review_board_reviewed_by')->nullable();
            $table->timestamp('rde_reviewed_at')->nullable();
            $table->unsignedBigInteger('rde_reviewed_by')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->text('revision_notes')->nullable();
            $table->json('revision_fields')->nullable();
            $table->json('revision_field_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();
        });
        Schema::create('thrusts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('keywords')->nullable();
            $table->boolean('active')->default(true);
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
        Schema::dropIfExists('submission_status_events');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('research');
        Schema::dropIfExists('thrusts');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('colleges');
        Schema::dropIfExists('admin_action_logs');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_rde_cannot_approve_a_submission_before_campus_board_approval(): void
    {
        $submitterId = DB::table('users')->insertGetId([
            'name' => 'Researcher',
            'role' => 'student',
            'status' => 'active',
        ]);
        $paperId = DB::table('research')->insertGetId([
            'title' => 'Awaiting Board review',
            'user_id' => $submitterId,
            'status' => Research::STATUS_PENDING_RDE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->from(route('submissions.rde'))
            ->withSession(['user_id' => 20, 'user_role' => 'super_admin'])
            ->post(route('submissions.rde-approve', $paperId))
            ->assertRedirect(route('submissions.rde'))
            ->assertSessionHas('error', 'Campus Review Board approval is required before RDE approval.');

        $this->assertDatabaseHas('research', [
            'id' => $paperId,
            'status' => Research::STATUS_PENDING_RDE,
            'published_at' => null,
        ]);
        $this->withSession(['user_id' => 20, 'user_role' => 'super_admin'])
            ->post(route('submissions.bulk-update'), [
                'research_ids' => [$paperId],
                'action' => 'approve',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Bulk action finished. Updated: 0. Skipped: 1.');
        $this->assertDatabaseHas('research', [
            'id' => $paperId,
            'status' => Research::STATUS_PENDING_RDE,
            'published_at' => null,
        ]);
    }

    public function test_campus_board_approval_is_recorded_before_rde_can_publish(): void
    {
        $collegeId = DB::table('colleges')->insertGetId([
            'name' => 'College of Arts and Sciences',
            'code' => 'CAS',
        ]);
        $submitterId = DB::table('users')->insertGetId([
            'name' => 'Researcher',
            'role' => 'student',
            'status' => 'active',
        ]);
        $boardId = DB::table('users')->insertGetId([
            'name' => 'Board Reviewer',
            'role' => 'campus_review_board',
            'status' => 'active',
        ]);
        $rdeId = DB::table('users')->insertGetId([
            'name' => 'RDE Reviewer',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        $paperId = DB::table('research')->insertGetId([
            'title' => 'Research with full workflow',
            'authors' => 'A. Researcher',
            'user_id' => $submitterId,
            'college_id' => $collegeId,
            'status' => Research::STATUS_PENDING_COLLEGE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->from(route('submissions.college'))
            ->withSession(['user_id' => 30, 'user_role' => 'admin', 'user_college_id' => $collegeId])
            ->post(route('submissions.college-approve', $paperId))
            ->assertRedirect(route('submissions.college'));
        $this->assertDatabaseHas('research', [
            'id' => $paperId,
            'status' => Research::STATUS_PENDING_CAMPUS_REVIEW,
        ]);
        $this->withSession(['user_id' => $boardId, 'user_role' => 'campus_review_board'])
            ->get(route('submissions.campus-review-board'))
            ->assertOk()
            ->assertSee('Research with full workflow')
            ->assertSee('Approve for RDE');

        $this->from(route('submissions.campus-review-board'))
            ->withSession(['user_id' => $boardId, 'user_role' => 'campus_review_board'])
            ->post(route('submissions.campus-review-board-approve', $paperId), [
                'comment' => 'Meets campus research standards.',
            ])
            ->assertRedirect(route('submissions.campus-review-board'));
        $this->assertDatabaseHas('research', [
            'id' => $paperId,
            'status' => Research::STATUS_PENDING_RDE,
            'campus_review_board_reviewed_by' => $boardId,
        ]);
        $this->assertDatabaseHas('submission_status_events', [
            'research_id' => $paperId,
            'action' => 'approved_by_campus_review_board',
            'actor_id' => $boardId,
            'notes' => 'Meets campus research standards.',
        ]);

        $this->from(route('submissions.rde'))
            ->withSession(['user_id' => $rdeId, 'user_role' => 'super_admin'])
            ->post(route('submissions.rde-approve', $paperId))
            ->assertRedirect(route('submissions.rde'));
        $this->assertDatabaseHas('research', [
            'id' => $paperId,
            'status' => Research::STATUS_APPROVED,
            'campus_review_board_reviewed_by' => $boardId,
            'rde_reviewed_by' => $rdeId,
            'accepted_by' => $rdeId,
            'published_by' => $rdeId,
        ]);
    }

    public function test_board_can_return_a_paper_with_a_reason_and_only_board_users_can_review(): void
    {
        $submitterId = DB::table('users')->insertGetId([
            'name' => 'Researcher',
            'role' => 'student',
            'status' => 'active',
        ]);
        $paperId = DB::table('research')->insertGetId([
            'title' => 'Paper requiring changes',
            'user_id' => $submitterId,
            'status' => Research::STATUS_PENDING_CAMPUS_REVIEW,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['user_id' => 40, 'user_role' => 'admin', 'user_college_id' => null])
            ->post(route('submissions.campus-review-board-approve', $paperId))
            ->assertRedirect(route('dashboard'));

        $this->from(route('submissions.campus-review-board'))
            ->withSession(['user_id' => 41, 'user_role' => 'campus_review_board'])
            ->post(route('submissions.campus-review-board-revision', $paperId), [
                'reason' => 'Clarify the sampling method and explain the participant selection criteria.',
            ])
            ->assertRedirect(route('submissions.campus-review-board'));

        $this->assertDatabaseHas('research', [
            'id' => $paperId,
            'status' => Research::STATUS_REVISION_CAMPUS_REVIEW,
            'revision_notes' => 'Clarify the sampling method and explain the participant selection criteria.',
            'campus_review_board_reviewed_by' => 41,
        ]);
        $this->assertDatabaseHas('submission_status_events', [
            'research_id' => $paperId,
            'action' => 'revision_requested_by_campus_review_board',
            'actor_id' => 41,
        ]);
        $notification = DB::table('notifications')
            ->where('notifiable_id', $submitterId)
            ->latest('created_at')
            ->first();
        $notificationData = json_decode($notification->data, true);
        $this->assertSame('revision_request', $notificationData['type']);
        $this->assertSame('Campus Review Board revision requested', $notificationData['title']);
        $this->assertSame(
            'Your submission "Paper requiring changes" needs changes before review can continue.',
            $notificationData['message']
        );

        $this->withSession(['user_id' => 41, 'user_role' => 'campus_review_board'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Campus Review Board')
            ->assertSee('Returned for revisions');
    }

    public function test_rde_revision_resubmission_returns_to_campus_review_board(): void
    {
        $collegeId = DB::table('colleges')->insertGetId([
            'name' => 'College of Arts and Sciences',
            'code' => 'CAS',
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Thesis',
        ]);
        $submitterId = DB::table('users')->insertGetId([
            'name' => 'Researcher',
            'role' => 'student',
            'status' => 'active',
        ]);
        $boardId = DB::table('users')->insertGetId([
            'name' => 'Board Reviewer',
            'role' => 'campus_review_board',
            'status' => 'active',
        ]);
        $paperId = DB::table('research')->insertGetId([
            'title' => 'Research returned by RDE',
            'authors' => 'A. Researcher',
            'abstract' => 'Abstract',
            'introduction' => 'Introduction',
            'methodology' => 'Methodology',
            'results' => 'Results',
            'references' => 'References',
            'conclusion' => 'Conclusion',
            'recommendations' => 'Recommendations',
            'keywords' => 'research',
            'college_id' => $collegeId,
            'category_id' => $categoryId,
            'academic_level' => 'BS',
            'publication_year' => now()->year,
            'user_id' => $submitterId,
            'status' => Research::STATUS_REVISION_RDE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['user_id' => $submitterId, 'user_role' => 'student'])
            ->put(route('research.update', ResearchToken::encode($paperId)), [
                'title' => 'Research returned by RDE',
                'abstract' => 'Updated abstract',
                'introduction' => 'Introduction',
                'methodology' => 'Methodology',
                'results' => 'Results',
                'references' => 'References',
                'conclusion' => 'Conclusion',
                'recommendations' => 'Recommendations',
                'keywords' => 'research',
                'authors' => 'A. Researcher',
                'college_id' => $collegeId,
                'category_id' => $categoryId,
                'academic_level' => 'BS',
                'publication_year' => now()->year,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Research paper updated and resubmitted for Campus Review Board review.');

        $this->assertDatabaseHas('research', [
            'id' => $paperId,
            'status' => Research::STATUS_PENDING_CAMPUS_REVIEW,
            'revision_notes' => null,
        ]);
        $this->assertDatabaseHas('submission_status_events', [
            'research_id' => $paperId,
            'action' => 'submission_updated_status_transition',
            'from_status' => Research::STATUS_REVISION_RDE,
            'to_status' => Research::STATUS_PENDING_CAMPUS_REVIEW,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $boardId,
        ]);
    }

    public function test_existing_rde_queue_items_are_routed_to_the_board_and_migration_rollback_is_scoped(): void
    {
        $submitterId = DB::table('users')->insertGetId([
            'name' => 'Researcher',
            'role' => 'student',
            'status' => 'active',
        ]);
        $routedPaperId = DB::table('research')->insertGetId([
            'title' => 'Existing RDE queue paper',
            'user_id' => $submitterId,
            'status' => Research::STATUS_PENDING_RDE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require base_path('database/migrations/2026_10_23_231103_route_pending_rde_research_through_campus_review_board.php');
        $migration->up();
        $this->assertDatabaseHas('research', [
            'id' => $routedPaperId,
            'status' => Research::STATUS_PENDING_CAMPUS_REVIEW,
        ]);
        $this->assertDatabaseHas('submission_status_events', [
            'research_id' => $routedPaperId,
            'action' => 'workflow_routed_to_campus_review_board',
        ]);

        $newPendingBoardPaperId = DB::table('research')->insertGetId([
            'title' => 'New Board queue paper',
            'user_id' => $submitterId,
            'status' => Research::STATUS_PENDING_CAMPUS_REVIEW,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $migration->down();

        $this->assertDatabaseHas('research', [
            'id' => $routedPaperId,
            'status' => Research::STATUS_PENDING_RDE,
        ]);
        $this->assertDatabaseHas('research', [
            'id' => $newPendingBoardPaperId,
            'status' => Research::STATUS_PENDING_CAMPUS_REVIEW,
        ]);
    }
}
