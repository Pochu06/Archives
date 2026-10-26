<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Research;
use App\Services\ResearchCategoryClassification;
use App\Support\ResearchToken;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ResearchCategoryClassificationTest extends TestCase
{
    private int $thesisId;

    private int $capstoneId;

    private int $dissertationId;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('research', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('academic_level', 2)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('college_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status')->nullable();
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
        Schema::create('admin_action_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('role');
            $table->string('action');
            $table->string('method');
            $table->string('route_name')->nullable();
            $table->string('path');
            $table->text('url');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_data')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
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

        $this->thesisId = Category::query()->create(['name' => 'Thesis'])->id;
        $this->capstoneId = Category::query()->create(['name' => 'Capstone Project'])->id;
        $this->dissertationId = Category::query()->create(['name' => 'Dissertation'])->id;
        DB::table('colleges')->insert(['name' => 'Test College', 'code' => 'TEST']);
        DB::table('users')->insert(['name' => 'Researcher']);
    }

    protected function tearDown(): void
    {
        foreach (['notifications', 'admin_action_logs', 'submission_status_events', 'thrusts', 'research', 'users', 'colleges', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_allowed_categories_are_scoped_to_the_selected_academic_level(): void
    {
        $classification = app(ResearchCategoryClassification::class);

        $this->assertTrue($classification->allows('BS', $this->thesisId));
        $this->assertTrue($classification->allows('BS', $this->capstoneId));
        $this->assertFalse($classification->allows('BS', $this->dissertationId));
        $this->assertTrue($classification->allows('GS', $this->thesisId));
        $this->assertTrue($classification->allows('GS', $this->dissertationId));
        $this->assertFalse($classification->allows('GS', $this->capstoneId));
    }

    public function test_invalid_classification_cannot_be_saved_through_submission_or_edit(): void
    {
        $this->withSession(['user_id' => 1, 'user_role' => 'student'])
            ->from(route('research.create'))
            ->post(route('research.store'), $this->paperPayload('BS', $this->dissertationId))
            ->assertRedirect(route('research.create'))
            ->assertSessionHasErrors([
                'category_id' => 'The selected research category does not match the academic level.',
            ]);

        $researchId = DB::table('research')->insertGetId([
            'title' => 'Legacy Paper',
            'user_id' => 1,
            'status' => Research::STATUS_PENDING_COLLEGE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['user_id' => 1, 'user_role' => 'student'])
            ->from(route('research.edit', ResearchToken::encode($researchId)))
            ->put(route('research.update', ResearchToken::encode($researchId)), $this->paperPayload('GS', $this->capstoneId))
            ->assertSessionHasErrors([
                'category_id' => 'The selected research category does not match the academic level.',
            ]);
    }

    public function test_draft_autosave_rejects_a_category_that_does_not_match_its_academic_level(): void
    {
        $this->withSession(['user_id' => 1, 'user_role' => 'student'])
            ->postJson(route('research.save-draft'), [
                'academic_level' => 'BS',
                'category_id' => $this->dissertationId,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id'])
            ->assertJsonPath('errors.category_id.0', 'The selected research category does not match the academic level.');
    }

    public function test_model_rejects_invalid_academic_level_category_pair(): void
    {
        $this->expectException(ValidationException::class);
        Research::query()->create([
            'title' => 'Invalid Pair',
            'academic_level' => 'BS',
            'category_id' => $this->dissertationId,
        ]);
    }

    public function test_authorized_review_corrects_legacy_metadata_and_audits_the_change(): void
    {
        $researchId = DB::table('research')->insertGetId([
            'title' => 'Legacy Research',
            'academic_level' => null,
            'category_id' => $this->thesisId,
            'college_id' => 1,
            'user_id' => 1,
            'status' => Research::STATUS_APPROVED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['user_id' => 1, 'user_role' => 'super_admin'])
            ->get(route('submissions.category-review'))
            ->assertOk()
            ->assertSee('Legacy Research')
            ->assertSee('not changed automatically');

        $this->withSession(['user_id' => 1, 'user_role' => 'super_admin'])
            ->put(route('submissions.category-classification.update', $researchId), [
                'academic_level' => 'GS',
                'category_id' => $this->dissertationId,
            ])
            ->assertRedirect(route('submissions.category-review'))
            ->assertSessionHas('success', 'Research classification corrected and recorded.');

        $this->assertDatabaseHas('research', [
            'id' => $researchId,
            'academic_level' => 'GS',
            'category_id' => $this->dissertationId,
        ]);
        $this->assertDatabaseHas('submission_status_events', [
            'research_id' => $researchId,
            'actor_id' => 1,
            'action' => 'category_classification_corrected',
        ]);
    }

    public function test_only_authorized_staff_can_correct_classification(): void
    {
        $researchId = DB::table('research')->insertGetId([
            'title' => 'Legacy Research',
            'category_id' => $this->thesisId,
            'user_id' => 1,
            'status' => Research::STATUS_PENDING_COLLEGE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['user_id' => 1, 'user_role' => 'student'])
            ->put(route('submissions.category-classification.update', $researchId), [
                'academic_level' => 'BS',
                'category_id' => $this->thesisId,
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'Unauthorized.');

        $this->assertDatabaseHas('research', [
            'id' => $researchId,
            'academic_level' => null,
        ]);
    }

    private function paperPayload(string $level, int $categoryId): array
    {
        return [
            'title' => 'A Research Paper',
            'abstract' => 'Abstract',
            'introduction' => 'Introduction',
            'methodology' => 'Methodology',
            'results' => 'Results',
            'references' => 'References',
            'conclusion' => 'Conclusion',
            'recommendations' => 'Recommendations',
            'keywords' => 'research',
            'authors' => 'Researcher',
            'college_id' => 1,
            'academic_level' => $level,
            'category_id' => $categoryId,
            'publication_year' => now()->year,
        ];
    }
}
