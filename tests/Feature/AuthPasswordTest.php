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

    public function test_login_submit_starts_disabled_until_recaptcha_completes(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('<button id="loginSubmitButton" type="submit" disabled', false)
            ->assertSee('data-callback="enableLoginButton"', false)
            ->assertSee('data-expired-callback="disableLoginButton"', false)
            ->assertSee('data-error-callback="disableLoginButton"', false);
    }
}
