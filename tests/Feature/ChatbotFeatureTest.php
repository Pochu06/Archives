<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Category;
use App\Models\Research;
use App\Models\User;
use App\Services\OllamaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ChatbotFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->string('dean')->nullable();
            $table->string('contact_email')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role')->default('student');
            $table->unsignedBigInteger('college_id')->nullable();
            $table->string('student_id')->nullable();
            $table->unsignedBigInteger('adviser_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken()->nullable();
            $table->timestamps();
        });

        Schema::create('research', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('abstract')->nullable();
            $table->longText('introduction')->nullable();
            $table->longText('methodology')->nullable();
            $table->longText('results')->nullable();
            $table->longText('discussion')->nullable();
            $table->longText('references')->nullable();
            $table->longText('conclusion')->nullable();
            $table->longText('recommendations')->nullable();
            $table->text('keywords')->nullable();
            $table->text('authors')->nullable();
            $table->unsignedBigInteger('college_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('publication_year')->nullable();
            $table->string('status')->default(Research::STATUS_PENDING_COLLEGE);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('revision_notes')->nullable();
            $table->text('revision_fields')->nullable();
            $table->text('revision_field_notes')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_chatbot_page_is_available(): void
    {
        $response = $this->get(route('chatbot.index'));

        $response->assertOk();
        $response->assertSee('Open AI Assistant');
    }

    public function test_chatbot_uses_ollama_and_shows_related_archive_records(): void
    {
        config(['services.ollama.enabled' => true]);

        $college = College::factory()->create([
            'name' => 'College of Computing',
            'code' => 'CCS',
        ]);
        $category = Category::query()->create([
            'name' => 'Information Technology',
            'description' => 'Technology studies',
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
            'college_id' => $college->id,
        ]);
        $research = Research::query()->create([
            'title' => 'Student Engagement Monitoring System for Online Classes',
            'abstract' => 'This study explores student engagement monitoring in online learning environments using analytics dashboards.',
            'introduction' => 'Introduction text',
            'methodology' => 'Methodology text',
            'results' => 'Results text',
            'discussion' => 'Discussion text',
            'references' => 'Reference text',
            'conclusion' => 'Conclusion text',
            'recommendations' => 'Recommendation text',
            'keywords' => 'student engagement, online classes, monitoring system',
            'authors' => 'Jane Doe',
            'college_id' => $college->id,
            'category_id' => $category->id,
            'user_id' => $student->id,
            'publication_year' => 2025,
            'status' => Research::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $ollama = Mockery::mock(OllamaService::class);
        $ollama->shouldReceive('isAvailable')->twice()->andReturn(true);
        $ollama->shouldReceive('chatMessages')
            ->once()
            ->withArgs(function (array $messages, array $options) {
                $payload = json_encode($messages);

                return str_contains($payload ?: '', 'Student Engagement Monitoring System for Online Classes')
                    && ($options['log_context'] ?? null) === 'archive chatbot response';
            })
            ->andReturn('The archive includes a study focused on monitoring student engagement in online classes.');

        $this->instance(OllamaService::class, $ollama);

        $response = $this->from(route('chatbot.index'))->post(route('chatbot.store'), [
            'message' => 'Find studies about student engagement in online classes.',
        ]);

        $response->assertRedirect(route('chatbot.index'));

        $this->get(route('chatbot.index'))
            ->assertOk()
            ->assertSee('The archive includes a study focused on monitoring student engagement in online classes.')
            ->assertSee($research->title);
    }

    public function test_chatbot_matches_broad_technology_queries_to_archive_records(): void
    {
        config(['services.ollama.enabled' => true]);

        $college = College::factory()->create([
            'name' => 'College of Information and Computing Sciences',
            'code' => 'CICS',
            'description' => 'Advancing computing, information technology, and data science research.',
        ]);
        $category = Category::query()->create([
            'name' => 'Research Paper',
            'description' => 'General academic research papers and studies.',
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
            'college_id' => $college->id,
        ]);
        $research = Research::query()->create([
            'title' => 'Artificial Intelligence in Educational Technology: Enhancing Student Learning Outcomes',
            'abstract' => 'This study examines the integration of artificial intelligence tools in educational settings and their measurable impact on student learning outcomes.',
            'introduction' => 'Introduction text',
            'methodology' => 'Methodology text',
            'results' => 'Results text',
            'discussion' => 'Discussion text',
            'references' => 'Reference text',
            'conclusion' => 'Conclusion text',
            'recommendations' => 'Recommendation text',
            'keywords' => 'artificial intelligence, educational technology, learning outcomes',
            'authors' => 'Jane Doe',
            'college_id' => $college->id,
            'category_id' => $category->id,
            'user_id' => $student->id,
            'publication_year' => 2025,
            'status' => Research::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $ollama = Mockery::mock(OllamaService::class);
        $ollama->shouldReceive('isAvailable')->twice()->andReturn(true);
        $ollama->shouldReceive('chatMessages')
            ->once()
            ->withArgs(function (array $messages, array $options) use ($research) {
                $payload = json_encode($messages);

                return str_contains($payload ?: '', $research->title)
                    && str_contains($payload ?: '', 'technologies')
                    && ($options['log_context'] ?? null) === 'archive chatbot response';
            })
            ->andReturn('I found archived technology-related research, including Artificial Intelligence in Educational Technology: Enhancing Student Learning Outcomes.');

        $this->instance(OllamaService::class, $ollama);

        $response = $this->from(route('chatbot.index'))->post(route('chatbot.store'), [
            'message' => 'can you find me research about technologies',
        ]);

        $response->assertRedirect(route('chatbot.index'));

        $this->get(route('chatbot.index'))
            ->assertOk()
            ->assertSee('I found archived technology-related research, including Artificial Intelligence in Educational Technology: Enhancing Student Learning Outcomes.')
            ->assertSee($research->title);
    }

    public function test_chatbot_strips_markdown_asterisks_from_assistant_titles(): void
    {
        config(['services.ollama.enabled' => true]);

        $college = College::factory()->create([
            'name' => 'College of Information and Computing Sciences',
            'code' => 'CICS',
        ]);
        $category = Category::query()->create([
            'name' => 'Research Paper',
            'description' => 'General academic research papers and studies.',
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
            'college_id' => $college->id,
        ]);
        Research::query()->create([
            'title' => 'Artificial Intelligence in Educational Technology: Enhancing Student Learning Outcomes',
            'abstract' => 'This study examines the integration of artificial intelligence tools in educational settings.',
            'introduction' => 'Introduction text',
            'methodology' => 'Methodology text',
            'results' => 'Results text',
            'discussion' => 'Discussion text',
            'references' => 'Reference text',
            'conclusion' => 'Conclusion text',
            'recommendations' => 'Recommendation text',
            'keywords' => 'artificial intelligence, educational technology',
            'authors' => 'Jane Doe',
            'college_id' => $college->id,
            'category_id' => $category->id,
            'user_id' => $student->id,
            'publication_year' => 2025,
            'status' => Research::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $ollama = Mockery::mock(OllamaService::class);
        $ollama->shouldReceive('isAvailable')->twice()->andReturn(true);
        $ollama->shouldReceive('chatMessages')
            ->once()
            ->andReturn('You should review **Artificial Intelligence in Educational Technology: Enhancing Student Learning Outcomes (2023)** for this topic.');

        $this->instance(OllamaService::class, $ollama);

        $response = $this->from(route('chatbot.index'))->post(route('chatbot.store'), [
            'message' => 'find me research about technology',
        ]);

        $response->assertRedirect(route('chatbot.index'));

        $this->get(route('chatbot.index'))
            ->assertOk()
            ->assertSee('You should review Artificial Intelligence in Educational Technology: Enhancing Student Learning Outcomes (2023) for this topic.')
            ->assertDontSee('**Artificial Intelligence in Educational Technology: Enhancing Student Learning Outcomes (2023)**');
    }
}