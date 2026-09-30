<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
            $table->timestamps();
        });

        \DB::table('colleges')->insert(['id' => 1]);
    }

    public function test_registration_requires_uppercase_number_symbol_and_eight_characters(): void
    {
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
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'student@example.com']);
    }
}
