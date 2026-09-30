<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthPasswordTest extends TestCase
{
    public function test_forgot_password_screen_is_available(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    public function test_reset_password_screen_is_available(): void
    {
        $this->get('/reset-password/test-token?email=test@example.com')->assertOk();
    }
}
