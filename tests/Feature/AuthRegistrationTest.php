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

        $this->assertDatabaseHas('users', ['email' => 'student@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'student@example.com', 'email_verified_at' => null]);
        $this->assertGuest();
        Notification::assertSentTo(User::where('email', 'student@example.com')->first(), VerifyEmail::class);
    }

    public function test_login_allows_unverified_users_after_captcha_validation(): void
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
                ->push(['success' => false]),
        ]);

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'Pass123!',
            'g-recaptcha-response' => 'valid-token',
        ])->assertRedirect(route('dashboard'))->assertSessionHas('user_id', $user->id);

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'Pass123!',
            'g-recaptcha-response' => 'invalid-token',
        ])->assertSessionHasErrors('g-recaptcha-response')->assertSessionMissing('user_id');
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
