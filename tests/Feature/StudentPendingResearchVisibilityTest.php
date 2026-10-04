<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\College;
use App\Models\Research;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentPendingResearchVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('role')->default('student');
            $table->unsignedBigInteger('college_id')->nullable();
            $table->timestamps();
        });
        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
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
            $table->text('abstract');
            $table->string('keywords');
            $table->string('authors');
            $table->unsignedBigInteger('college_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status');
            $table->integer('publication_year');
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();
        });
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('context');
            $table->string('name');
            $table->json('filters')->nullable();
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
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('saved_searches');
        Schema::dropIfExists('research');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('colleges');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_student_archive_and_dashboard_hide_pending_papers(): void
    {
        $college = College::create([
            'name' => 'Test College',
            'code' => 'TEST',
        ]);
        $category = Category::create([
            'name' => 'Test Category',
        ]);
        $student = User::create([
            'name' => 'Test Student',
            'email' => 'student@example.test',
            'password' => 'password',
            'role' => 'student',
            'college_id' => $college->id,
        ]);

        Research::create([
            'title' => 'Approved Student Paper',
            'abstract' => 'Approved abstract',
            'keywords' => 'approved',
            'authors' => 'Test Student',
            'college_id' => $college->id,
            'category_id' => $category->id,
            'user_id' => $student->id,
            'status' => Research::STATUS_APPROVED,
            'publication_year' => 2026,
        ]);
        Research::create([
            'title' => 'Pending Student Paper',
            'abstract' => 'Pending abstract',
            'keywords' => 'pending',
            'authors' => 'Test Student',
            'college_id' => $college->id,
            'category_id' => $category->id,
            'user_id' => $student->id,
            'status' => Research::STATUS_PENDING_COLLEGE,
            'publication_year' => 2026,
        ]);
        $otherCollege = College::create([
            'name' => 'Other College',
            'code' => 'OTHER',
        ]);
        $olderCollegePaper = Research::create([
            'title' => 'Older College Paper',
            'abstract' => 'Older college abstract',
            'keywords' => 'popular',
            'authors' => 'Test Student',
            'college_id' => $college->id,
            'category_id' => $category->id,
            'user_id' => $student->id,
            'status' => Research::STATUS_APPROVED,
            'publication_year' => 2025,
            'view_count' => 6,
            'download_count' => 4,
        ]);
        $olderCollegePaper->forceFill(['created_at' => now()->subDay()])->save();
        Research::create([
            'title' => 'Newer College Paper',
            'abstract' => 'Newer college abstract',
            'keywords' => 'popular',
            'authors' => 'Test Student',
            'college_id' => $college->id,
            'category_id' => $category->id,
            'user_id' => $student->id,
            'status' => Research::STATUS_APPROVED,
            'publication_year' => 2026,
            'view_count' => 5,
            'download_count' => 5,
        ]);
        Research::create([
            'title' => 'Popular Other College Paper',
            'abstract' => 'Popular other college abstract',
            'keywords' => 'popular',
            'authors' => 'Test Student',
            'college_id' => $otherCollege->id,
            'category_id' => $category->id,
            'user_id' => $student->id,
            'status' => Research::STATUS_APPROVED,
            'publication_year' => 2026,
            'view_count' => 100,
            'download_count' => 100,
        ]);

        $session = [
            'user_id' => $student->id,
            'user_role' => 'student',
            'user_college_id' => $college->id,
            'user_name' => $student->name,
        ];

        $archiveResponse = $this->withSession($session)
            ->get(route('research.index'))
            ->assertOk()
            ->assertSee('Approved Student Paper')
            ->assertDontSee('Pending Student Paper')
            ->assertDontSee('<select name="status"', false)
            ->assertDontSee('Pending Review')
            ->assertDontSee('Needs Revision');
        $this->assertTitlesAppearInOrder($archiveResponse->getContent(), [
            'Newer College Paper',
            'Older College Paper',
            'Approved Student Paper',
            'Popular Other College Paper',
        ]);

        $publicResponse = $this->withSession($session)
            ->get(route('research.public'))
            ->assertOk()
            ->assertDontSee('Pending Student Paper');
        $this->assertTitlesAppearInOrder($publicResponse->getContent(), [
            'Newer College Paper',
            'Older College Paper',
            'Approved Student Paper',
            'Popular Other College Paper',
        ]);

        $dashboardResponse = $this->withSession($session)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Newer College Paper')
            ->assertSee('Older College Paper')
            ->assertSee('Approved Student Paper')
            ->assertDontSee('Pending Student Paper')
            ->assertSee('>4</p>', false);
        $dashboardHtml = $dashboardResponse->getContent();
        $this->assertStringContainsString('Browse Archive', $dashboardHtml);
        $browseSection = substr($dashboardHtml, strpos($dashboardHtml, 'Browse Archive'));
        $this->assertTitlesAppearInOrder($browseSection, [
            'Newer College Paper',
            'Older College Paper',
            'Approved Student Paper',
            'Popular Other College Paper',
        ]);
    }

    private function assertTitlesAppearInOrder(string $html, array $titles): void
    {
        $lastPosition = -1;

        foreach ($titles as $title) {
            $position = strpos($html, $title);
            $this->assertNotFalse($position, "Expected [{$title}] to be present.");
            $this->assertGreaterThan($lastPosition, $position, "Expected [{$title}] to appear in the required order.");
            $lastPosition = $position;
        }
    }
}
