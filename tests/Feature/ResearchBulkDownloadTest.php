<?php

namespace Tests\Feature;

use App\Models\Research;
use App\Support\ResearchToken;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;
use ZipArchive;

class ResearchBulkDownloadTest extends TestCase
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
            $table->text('abstract')->nullable();
            $table->text('introduction')->nullable();
            $table->text('methodology')->nullable();
            $table->text('results')->nullable();
            $table->text('discussion')->nullable();
            $table->text('references')->nullable();
            $table->text('conclusion')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('keywords')->nullable();
            $table->string('authors')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('college_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('academic_program')->nullable();
            $table->unsignedInteger('publication_year')->nullable();
            $table->string('table_design')->nullable();
            $table->string('status');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
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
        Schema::create('download_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('research_id');
            $table->string('status');
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
            $table->timestamps();
        });
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
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
        foreach ([
            'admin_action_logs', 'system_settings', 'journal_issues', 'download_requests',
            'submission_status_events', 'research', 'categories', 'colleges', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_single_download_still_returns_a_pdf_with_a_safe_filename(): void
    {
        $collegeId = DB::table('colleges')->insertGetId(['name' => 'Science College', 'code' => 'SCI']);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Thesis']);
        $userId = $this->insertUser('Student', 'student', $collegeId);
        $researchId = $this->insertResearch($userId, $collegeId, $categoryId, 'Unsafe: / Paper title');

        $response = $this->withSession(['user_id' => $userId, 'user_role' => 'student'])
            ->get(route('research.download', ResearchToken::encode($researchId)));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=unsafe-paper-title.pdf');
        $this->assertSame(1, DB::table('research')->where('id', $researchId)->value('download_count'));
    }

    public function test_single_download_still_denies_another_users_private_submission(): void
    {
        $collegeId = DB::table('colleges')->insertGetId(['name' => 'Science College', 'code' => 'SCI']);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Thesis']);
        $studentId = $this->insertUser('Student', 'student', $collegeId);
        $ownerId = $this->insertUser('Other Student', 'student', $collegeId);
        $researchId = $this->insertResearch($ownerId, $collegeId, $categoryId, 'Private Research');

        $this->from('/research')->withSession(['user_id' => $studentId, 'user_role' => 'student'])
            ->get(route('research.download', ResearchToken::encode($researchId)))
            ->assertRedirect('/research')
            ->assertSessionHas('error', 'You need an approved download request to download this paper.');

        $this->assertSame(0, DB::table('research')->where('id', $researchId)->value('download_count'));
    }

    public function test_bulk_download_zips_only_authorized_papers_and_audits_the_result(): void
    {
        $collegeId = DB::table('colleges')->insertGetId(['name' => 'Science College', 'code' => 'SCI']);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Thesis']);
        $studentId = $this->insertUser('Student', 'student', $collegeId);
        $otherStudentId = $this->insertUser('Other Student', 'student', $collegeId);
        $ownPaperId = $this->insertResearch($studentId, $collegeId, $categoryId, 'My Research');
        $privatePaperId = $this->insertResearch($otherStudentId, $collegeId, $categoryId, 'Private Research');

        $response = $this->withSession(['user_id' => $studentId, 'user_role' => 'student'])
            ->post(route('research.bulk-download'), [
                'research_ids' => [$ownPaperId, $privatePaperId],
            ]);

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $archivePath = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        try {
            $this->assertTrue($zip->open($archivePath));
            $this->assertSame('my-research-'.$ownPaperId.'.pdf', $zip->getNameIndex(0));
            $this->assertSame('DOWNLOAD-INFO.txt', $zip->getNameIndex(1));
            $this->assertStringContainsString('Documents omitted: 1', $zip->getFromName('DOWNLOAD-INFO.txt'));
            $this->assertSame(false, $zip->locateName('private-research-'.$privatePaperId.'.pdf'));
        } finally {
            $zip->close();
            if (is_file($archivePath)) {
                unlink($archivePath);
            }
        }

        $this->assertSame(1, DB::table('research')->where('id', $ownPaperId)->value('download_count'));
        $this->assertSame(0, DB::table('research')->where('id', $privatePaperId)->value('download_count'));
        $this->assertDatabaseHas('admin_action_logs', [
            'user_id' => $studentId,
            'action' => 'research.bulk_download',
            'response_status' => 200,
        ]);
        $activity = DB::table('admin_action_logs')->where('action', 'research.bulk_download')->first();
        $this->assertSame(1, json_decode($activity->request_data, true)['included_count']);
        $this->assertSame(1, json_decode($activity->request_data, true)['excluded_count']);
    }

    public function test_bulk_download_reports_when_no_selected_papers_are_authorized(): void
    {
        $collegeId = DB::table('colleges')->insertGetId(['name' => 'Science College', 'code' => 'SCI']);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Thesis']);
        $studentId = $this->insertUser('Student', 'student', $collegeId);
        $ownerId = $this->insertUser('Other Student', 'student', $collegeId);
        $privatePaperId = $this->insertResearch($ownerId, $collegeId, $categoryId, 'Private Research');

        $this->from('/research')->withSession(['user_id' => $studentId, 'user_role' => 'student'])
            ->post(route('research.bulk-download'), ['research_ids' => [$privatePaperId]])
            ->assertRedirect('/research')
            ->assertSessionHas('error', 'No eligible papers were selected. Check that you own the papers or have an approved download request.');

        $activity = DB::table('admin_action_logs')->where('action', 'research.bulk_download')->first();
        $this->assertSame('blocked', json_decode($activity->request_data, true)['result']);
        $this->assertSame(1, json_decode($activity->request_data, true)['excluded_count']);
    }

    public function test_bulk_download_enforces_the_configured_file_limit_and_rejects_duplicates(): void
    {
        $collegeId = DB::table('colleges')->insertGetId(['name' => 'Science College', 'code' => 'SCI']);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Thesis']);
        $studentId = $this->insertUser('Student', 'student', $collegeId);
        $firstPaperId = $this->insertResearch($studentId, $collegeId, $categoryId, 'First Research');
        $secondPaperId = $this->insertResearch($studentId, $collegeId, $categoryId, 'Second Research');
        DB::table('system_settings')->insert([
            'key' => 'bulk_download_max_files',
            'value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $session = ['user_id' => $studentId, 'user_role' => 'student'];
        $this->from('/research')->withSession($session)
            ->post(route('research.bulk-download'), ['research_ids' => [$firstPaperId, $secondPaperId]])
            ->assertRedirect('/research')
            ->assertSessionHasErrors('research_ids');

        $this->from('/research')->withSession($session)
            ->post(route('research.bulk-download'), ['research_ids' => [$firstPaperId, $firstPaperId]])
            ->assertRedirect('/research')
            ->assertSessionHasErrors('research_ids.1');

        $this->assertSame(0, DB::table('research')->sum('download_count'));
        $this->assertSame(2, DB::table('admin_action_logs')->where('action', 'research.bulk_download')->count());
    }

    public function test_super_admin_can_configure_bulk_download_limits(): void
    {
        $studentId = $this->insertUser('Student', 'student');
        $this->withSession(['user_id' => $studentId, 'user_role' => 'student'])
            ->post(route('settings.bulk-download'), ['max_files' => 5, 'max_size_mb' => 50])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'Unauthorized.');
        $this->assertDatabaseMissing('system_settings', ['key' => 'bulk_download_max_files']);

        $adminId = $this->insertUser('System Admin', 'super_admin');

        $this->withSession(['user_id' => $adminId, 'user_role' => 'super_admin'])
            ->post(route('settings.bulk-download'), ['max_files' => 12, 'max_size_mb' => 200])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Bulk-download limits updated.');

        $this->assertDatabaseHas('system_settings', ['key' => 'bulk_download_max_files', 'value' => '12']);
        $this->assertDatabaseHas('system_settings', ['key' => 'bulk_download_max_size_mb', 'value' => '200']);
    }

    private function insertUser(string $name, string $role, ?int $collegeId = null): int
    {
        return DB::table('users')->insertGetId([
            'name' => $name,
            'role' => $role,
            'college_id' => $collegeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertResearch(int $userId, int $collegeId, int $categoryId, string $title): int
    {
        return DB::table('research')->insertGetId([
            'title' => $title,
            'authors' => 'Researcher',
            'user_id' => $userId,
            'college_id' => $collegeId,
            'category_id' => $categoryId,
            'publication_year' => 2026,
            'status' => Research::STATUS_PENDING_COLLEGE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
