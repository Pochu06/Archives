<?php

namespace Tests\Feature;

use App\Models\Research;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResearchLifecycleTimestampTest extends TestCase
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
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('status')->default('pending_college');
            $table->timestamps();
        });
        Schema::create('submission_status_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('research_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action');
            $table->timestamp('created_at')->nullable();
        });

        $lifecycleMigration = require base_path('database/migrations/2026_10_23_225059_add_lifecycle_tracking_to_research_table.php');
        $lifecycleMigration->up();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('research');
        Schema::dropIfExists('submission_status_events');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_lifecycle_stages_are_recorded_once_with_their_responsible_users(): void
    {
        $firstUserId = DB::table('users')->insertGetId(['name' => 'First Reviewer']);
        $secondUserId = DB::table('users')->insertGetId(['name' => 'Later Reviewer']);
        $paper = Research::create([
            'user_id' => $firstUserId,
            'status' => Research::STATUS_PENDING_RDE,
        ]);
        $firstReviewedAt = Carbon::parse('2026-10-23 09:00:00');
        $laterReviewedAt = Carbon::parse('2026-10-24 09:00:00');

        $this->assertTrue($paper->recordLifecycleStage('rde_reviewed', $firstUserId, $firstReviewedAt));
        $this->assertFalse($paper->recordLifecycleStage('rde_reviewed', $secondUserId, $laterReviewedAt));
        $this->assertTrue($paper->recordLifecycleStage('accepted', $firstUserId, $firstReviewedAt));
        $this->assertTrue($paper->recordLifecycleStage('published', $firstUserId, $firstReviewedAt));

        $paper->update(['status' => Research::STATUS_REJECTED_RDE]);
        $this->assertFalse($paper->recordLifecycleStage('accepted', $secondUserId, $laterReviewedAt));
        $this->assertFalse($paper->recordLifecycleStage('published', $secondUserId, $laterReviewedAt));

        $paper->refresh();
        $this->assertEquals($firstReviewedAt, $paper->rde_reviewed_at);
        $this->assertSame($firstUserId, $paper->rde_reviewed_by);
        $this->assertEquals($firstReviewedAt, $paper->accepted_at);
        $this->assertSame($firstUserId, $paper->accepted_by);
        $this->assertEquals($firstReviewedAt, $paper->published_at);
        $this->assertSame($firstUserId, $paper->published_by);
        $this->assertEquals($firstReviewedAt, $paper->approved_at);
        $this->assertSame($firstUserId, $paper->approved_by);
    }

    public function test_backfill_uses_existing_submission_and_review_history(): void
    {
        $submitterId = DB::table('users')->insertGetId(['name' => 'Submitter']);
        $reviewerId = DB::table('users')->insertGetId(['name' => 'Reviewer']);
        $submittedAt = '2026-09-01 10:00:00';
        $collegeReviewedAt = '2026-09-02 11:00:00';
        $rdeReviewedAt = '2026-09-03 12:00:00';
        $acceptedAt = '2026-09-04 13:00:00';
        $pendingPaperId = DB::table('research')->insertGetId([
            'user_id' => $submitterId,
            'status' => Research::STATUS_PENDING_RDE,
            'created_at' => $submittedAt,
            'updated_at' => $submittedAt,
        ]);
        $approvedPaperId = DB::table('research')->insertGetId([
            'user_id' => $submitterId,
            'approved_by' => $reviewerId,
            'approved_at' => $acceptedAt,
            'status' => Research::STATUS_APPROVED,
            'created_at' => $submittedAt,
            'updated_at' => $acceptedAt,
        ]);
        DB::table('submission_status_events')->insert([
            [
                'research_id' => $pendingPaperId,
                'actor_id' => $reviewerId,
                'action' => 'approved_by_college',
                'created_at' => $collegeReviewedAt,
            ],
            [
                'research_id' => $pendingPaperId,
                'actor_id' => $reviewerId,
                'action' => 'revision_requested_by_rde',
                'created_at' => $rdeReviewedAt,
            ],
        ]);

        $backfillMigration = require base_path('database/migrations/2026_10_23_225405_backfill_existing_research_lifecycle_timestamps.php');
        $backfillMigration->up();

        $pendingPaper = Research::findOrFail($pendingPaperId);
        $this->assertEquals(Carbon::parse($submittedAt), $pendingPaper->submitted_at);
        $this->assertSame($submitterId, $pendingPaper->submitted_by);
        $this->assertEquals(Carbon::parse($collegeReviewedAt), $pendingPaper->college_reviewed_at);
        $this->assertSame($reviewerId, $pendingPaper->college_reviewed_by);
        $this->assertEquals(Carbon::parse($rdeReviewedAt), $pendingPaper->rde_reviewed_at);
        $this->assertSame($reviewerId, $pendingPaper->rde_reviewed_by);

        $approvedPaper = Research::findOrFail($approvedPaperId);
        $this->assertEquals(Carbon::parse($acceptedAt), $approvedPaper->rde_reviewed_at);
        $this->assertSame($reviewerId, $approvedPaper->rde_reviewed_by);
        $this->assertEquals(Carbon::parse($acceptedAt), $approvedPaper->accepted_at);
        $this->assertSame($reviewerId, $approvedPaper->accepted_by);
        $this->assertEquals(Carbon::parse($acceptedAt), $approvedPaper->published_at);
        $this->assertSame($reviewerId, $approvedPaper->published_by);
    }
}
