<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    /**
     * Email verification tests are skipped because the system uses username-based
     * authentication and email verification has been removed from the user model.
     */
    public function test_email_verification_screen_can_be_rendered(): void
    {
        $this->markTestSkipped('Email verification removed: system uses username-based auth.');
    }

    public function test_email_can_be_verified(): void
    {
        $this->markTestSkipped('Email verification removed: system uses username-based auth.');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $this->markTestSkipped('Email verification removed: system uses username-based auth.');
    }
}
