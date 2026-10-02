<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->unsignedBigInteger('college_id');
            $table->string('student_id')->nullable();
            $table->string('status');
            $table->timestamp('email_verified_at')->nullable();
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

        \DB::table('colleges')->insert(['id' => 1]);
    }

    public function test_registration_requires_uppercase_number_symbol_and_eight_characters(): void
    {
        Notification::fake();

        foreach (['password1!', 'Password!', 'Password1', 'Pass1!', 'Pass 123'] as $password) {
            $this->post(route('register.post'), [
                'name' => 'Test Student',
                'email' => 'student@example.com',
                'college_id' => 1,
                'password' => $password,
                'password_confirmation' => $password,
            ])->assertSessionHasErrors('password');
        }

        $this->post(route('register.post'), [
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'college_id' => 1,
            'password' => 'Pass123!',
            'password_confirmation' => 'Pass123!',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['email' => 'student@example.com', 'status' => 'pending']);
        $this->assertDatabaseHas('users', ['email' => 'student@example.com', 'email_verified_at' => null]);
        $this->assertGuest();
        Notification::assertSentTo(User::where('email', 'student@example.com')->first(), VerifyEmail::class);
    }

    public function test_pending_account_cannot_log_in_until_approved(): void
    {
        config(['services.recaptcha.site_key' => 'test-site-key', 'services.recaptcha.secret_key' => 'test-secret']);

        $user = User::create([
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'password' => Hash::make('Pass123!'),
            'role' => 'student',
            'college_id' => 1,
            'status' => 'pending',
            'email_verified_at' => now(),
        ]);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => ['success' => true],
        ]);

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'Pass123!',
            'g-recaptcha-response' => 'valid-token',
        ])->assertSessionHasErrors('email')->assertSessionMissing('user_id');
    }

    public function test_admin_can_view_and_approve_pending_registration_for_their_college(): void
    {
        $user = User::create([
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'password' => Hash::make('Pass123!'),
            'role' => 'student',
            'college_id' => 1,
            'status' => 'pending',
        ]);

        $this->withSession([
            'user_id' => 10,
            'user_role' => 'admin',
            'user_college_id' => 1,
        ])->get(route('users.pending'))
            ->assertOk()
            ->assertSee('student@example.com')
            ->assertSee('Approve');

        $this->post(route('users.approve', $user->id))
            ->assertRedirect(route('users.pending'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'active']);
    }

    public function test_college_admin_cannot_approve_a_registration_from_another_college(): void
    {
        $user = User::create([
            'name' => 'Other College Student',
            'email' => 'other@example.com',
            'password' => Hash::make('Pass123!'),
            'role' => 'student',
            'college_id' => 2,
            'status' => 'pending',
        ]);

        $this->withSession([
            'user_id' => 10,
            'user_role' => 'admin',
            'user_college_id' => 1,
        ])->post(route('users.approve', $user->id))
            ->assertRedirect(route('users.pending'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'pending']);
    }

    public function test_pending_account_cannot_be_activated_from_user_edit(): void
    {
        $user = User::create([
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'password' => Hash::make('Pass123!'),
            'role' => 'student',
            'college_id' => 1,
            'status' => 'pending',
        ]);

        $this->withSession([
            'user_id' => 10,
            'user_role' => 'admin',
            'user_college_id' => 1,
        ])->put(route('users.update', $user->id), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'college_id' => $user->college_id,
            'student_id' => null,
            'status' => 'active',
        ])->assertRedirect(route('users.pending'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'pending']);
    }

    public function test_login_requires_email_verification_and_successful_captcha(): void
    {
        config(['services.recaptcha.site_key' => 'test-site-key', 'services.recaptcha.secret_key' => 'test-secret']);

        $user = User::create([
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'password' => Hash::make('Pass123!'),
            'role' => 'student',
            'college_id' => 1,
            'status' => 'active',
        ]);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::sequence()
                ->push(['success' => true])
                ->push(['success' => false])
                ->push(['success' => true]),
        ]);

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'Pass123!',
            'g-recaptcha-response' => 'valid-token',
        ])->assertSessionHasErrors('email')->assertSessionMissing('user_id');

        $user->markEmailAsVerified();

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'Pass123!',
            'g-recaptcha-response' => 'invalid-token',
        ])->assertSessionHasErrors('g-recaptcha-response')->assertSessionMissing('user_id');

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'Pass123!',
            'g-recaptcha-response' => 'valid-token',
        ])->assertRedirect(route('dashboard'))->assertSessionHas('user_id', $user->id);
    }

    public function test_email_verification_requires_a_valid_signed_link(): void
    {
        $user = User::create([
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'password' => Hash::make('Pass123!'),
            'role' => 'student',
            'college_id' => 1,
            'status' => 'active',
        ]);

        $this->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))
            ->assertForbidden();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinute(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($url)->assertRedirect(route('login'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
