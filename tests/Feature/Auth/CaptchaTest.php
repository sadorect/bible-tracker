<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\CaptchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CaptchaTest extends TestCase
{
    use RefreshDatabase;

    protected function useTurnstile(): void
    {
        config([
            'security.captcha.enabled' => true,
            'security.captcha.driver' => null,
            'security.captcha.turnstile.site_key' => 'site-key',
            'security.captcha.turnstile.secret_key' => 'secret-key',
        ]);
    }

    protected function useImageCaptcha(): void
    {
        config([
            'security.captcha.enabled' => true,
            'security.captcha.driver' => null,
            'security.captcha.turnstile.site_key' => null,
            'security.captcha.turnstile.secret_key' => null,
        ]);
    }

    public function test_driver_falls_back_to_the_image_captcha_when_keys_are_absent(): void
    {
        $this->useImageCaptcha();

        $this->assertSame(CaptchaService::DRIVER_IMAGE, app(CaptchaService::class)->driver());
        $this->assertFalse(app(CaptchaService::class)->usesTurnstile());
    }

    public function test_driver_switches_to_turnstile_once_both_keys_are_configured(): void
    {
        $this->useTurnstile();

        $this->assertSame(CaptchaService::DRIVER_TURNSTILE, app(CaptchaService::class)->driver());
    }

    public function test_a_valid_token_passes_verification(): void
    {
        $this->useTurnstile();

        Http::fake(['*/siteverify' => Http::response(['success' => true])]);

        $this->assertTrue(app(CaptchaService::class)->verify('good-token'));

        Http::assertSent(fn ($request) => $request['secret'] === 'secret-key'
            && $request['response'] === 'good-token');
    }

    public function test_a_rejected_token_fails_verification(): void
    {
        $this->useTurnstile();

        Http::fake(['*/siteverify' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ])]);

        $this->assertFalse(app(CaptchaService::class)->verify('bad-token'));
    }

    public function test_an_empty_token_is_rejected_without_calling_cloudflare(): void
    {
        $this->useTurnstile();

        Http::fake();

        $this->assertFalse(app(CaptchaService::class)->verify('  '));

        Http::assertNothingSent();
    }

    public function test_verification_fails_closed_when_cloudflare_is_unreachable(): void
    {
        $this->useTurnstile();

        Http::fake(fn () => throw new ConnectionException('unreachable'));

        $this->assertFalse(app(CaptchaService::class)->verify('any-token'));
    }

    public function test_verification_fails_closed_on_a_server_error_response(): void
    {
        $this->useTurnstile();

        Http::fake(['*/siteverify' => Http::response('', 500)]);

        $this->assertFalse(app(CaptchaService::class)->verify('any-token'));
    }

    public function test_captcha_is_skipped_entirely_when_disabled(): void
    {
        $this->useTurnstile();
        config(['security.captcha.enabled' => false]);

        Http::fake();

        $this->assertTrue(app(CaptchaService::class)->verify(''));

        Http::assertNothingSent();
    }

    public function test_failure_message_matches_the_active_driver(): void
    {
        $this->useTurnstile();
        $this->assertStringContainsString('security check', app(CaptchaService::class)->failureMessage());

        config(['security.captcha.driver' => CaptchaService::DRIVER_IMAGE]);
        $this->assertStringContainsString('security code', app(CaptchaService::class)->failureMessage());
    }

    public function test_auth_forms_render_the_turnstile_widget_instead_of_an_image(): void
    {
        $this->useTurnstile();

        foreach (['login', 'register', 'password.request'] as $route) {
            $response = $this->get(route($route));

            $response->assertOk();
            $response->assertSee('challenges.cloudflare.com/turnstile', false);
            $response->assertSee('site-key', false);
            $response->assertDontSee(route('captcha.image'), false);
        }
    }

    public function test_auth_forms_render_the_image_fallback_when_turnstile_is_unconfigured(): void
    {
        $this->useImageCaptcha();

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee(route('captcha.image'), false);
        $response->assertDontSee('challenges.cloudflare.com/turnstile', false);
    }

    public function test_the_image_endpoint_serves_a_png(): void
    {
        $this->useImageCaptcha();

        $response = $this->get(route('captcha.image'));

        $response->assertOk();
        $response->assertHeader('content-type', 'image/png');
    }

    public function test_the_math_captcha_is_gone(): void
    {
        $this->assertFalse(Route::has('captcha.refresh'));

        $this->useTurnstile();
        $this->get(route('login'))->assertDontSee('captcha_answer', false);
    }

    public function test_login_is_rejected_when_the_honeypot_is_filled(): void
    {
        $this->useTurnstile();
        Http::fake(['*/siteverify' => Http::response(['success' => true])]);

        $user = User::factory()->create();

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
            'captcha' => 'token',
            'website' => 'http://spam.example',
        ]);

        $response->assertSessionHasErrors('website');
        $this->assertGuest();
    }

    public function test_login_is_rejected_when_turnstile_rejects_the_token(): void
    {
        $this->useTurnstile();
        Http::fake(['*/siteverify' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ])]);

        $user = User::factory()->create();

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
            'captcha' => 'bad-token',
        ]);

        $response->assertSessionHasErrors('captcha');
        $this->assertGuest();
    }

    public function test_login_succeeds_with_a_valid_token_and_empty_honeypot(): void
    {
        $this->useTurnstile();
        Http::fake(['*/siteverify' => Http::response(['success' => true])]);

        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
            'captcha' => 'good-token',
            'website' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }
}
