<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    /**
     * Password reset tests are skipped because the system uses username-based
     * authentication and the password reset via email flow has been removed.
     */
    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        $this->markTestSkipped('Password reset via email removed: system uses username-based auth.');
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $this->markTestSkipped('Password reset via email removed: system uses username-based auth.');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $this->markTestSkipped('Password reset via email removed: system uses username-based auth.');
    }
}
