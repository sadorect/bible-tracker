<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaptchaRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_captcha_can_be_refreshed_via_post_request(): void
    {
        $response = $this->post(route('captcha.refresh', ['for' => 'login']));

        $response->assertOk()
            ->assertJsonStructure(['a', 'b']);

        $this->assertIsInt(session('captcha_login_a'));
        $this->assertIsInt(session('captcha_login_b'));
        $this->assertSame(
            session('captcha_login_a') + session('captcha_login_b'),
            session('captcha_login_sum'),
        );
    }
}
