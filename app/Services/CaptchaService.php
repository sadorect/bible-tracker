<?php

namespace App\Services;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CaptchaService
{
    public const DRIVER_TURNSTILE = 'turnstile';

    public const DRIVER_IMAGE = 'image';

    private const SESSION_KEY = 'captcha_code';

    private const TOKEN_KEY = 'captcha_token';

    public function enabled(): bool
    {
        $configured = config('security.captcha.enabled');

        if ($configured !== null) {
            return (bool) $configured;
        }

        return ! app()->environment('testing');
    }

    /**
     * Which challenge is in use. Turnstile whenever it is configured, and the
     * image captcha otherwise, so an unconfigured environment (local, CI) still
     * has a working form rather than an unsolvable one.
     */
    public function driver(): string
    {
        $configured = config('security.captcha.driver');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return $this->turnstileConfigured() ? self::DRIVER_TURNSTILE : self::DRIVER_IMAGE;
    }

    public function usesTurnstile(): bool
    {
        return $this->driver() === self::DRIVER_TURNSTILE;
    }

    public function siteKey(): string
    {
        return (string) config('security.captcha.turnstile.site_key');
    }

    /**
     * User-facing copy for a failed challenge. Turnstile has no code to type,
     * so telling the visitor their "security code" was wrong would be nonsense.
     */
    public function failureMessage(): string
    {
        return $this->usesTurnstile()
            ? 'The security check could not be completed. Please wait a moment and try again.'
            : 'The security code you entered is incorrect. Please try again.';
    }

    protected function turnstileConfigured(): bool
    {
        return filled(config('security.captcha.turnstile.site_key'))
            && filled(config('security.captcha.turnstile.secret_key'));
    }

    /**
     * Verify a challenge response.
     *
     * $input is the typed code under the image driver, and the
     * cf-turnstile-response token under Turnstile. Callers do not care which.
     */
    public function verify(string $input): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        return $this->usesTurnstile()
            ? $this->verifyTurnstile($input)
            : $this->verifyImageCode($input);
    }

    /**
     * Exchange a Turnstile token with Cloudflare's siteverify endpoint.
     *
     * Tokens are single-use and expire after ~5 minutes; Cloudflare enforces
     * both, so a replayed token comes back as invalid without extra work here.
     *
     * A network failure fails CLOSED. The app is served exclusively through
     * Cloudflare, so an unreachable siteverify almost certainly means visitors
     * cannot obtain a token either — failing open would only widen the hole at
     * the exact moment we cannot check anything.
     */
    protected function verifyTurnstile(string $token): bool
    {
        $token = trim($token);

        if ($token === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('security.captcha.turnstile.timeout', 5))
                ->post((string) config('security.captcha.turnstile.verify_url'), [
                    'secret' => (string) config('security.captcha.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);
        } catch (Throwable $e) {
            Log::error('Turnstile verification request failed.', ['message' => $e->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            Log::error('Turnstile verification returned an unexpected status.', [
                'status' => $response->status(),
            ]);

            return false;
        }

        if ($response->json('success') === true) {
            return true;
        }

        $errors = (array) $response->json('error-codes', []);

        // Operator-side faults (bad/missing secret) are logged loudly — they
        // would otherwise look identical to a visitor failing the challenge.
        if (array_intersect($errors, ['invalid-input-secret', 'missing-input-secret'])) {
            Log::error('Turnstile secret key is missing or invalid.', ['error_codes' => $errors]);
        }

        return false;
    }

    /**
     * Validation rules for the challenge field, so every form states the
     * requirement the same way.
     *
     * @return array<int, mixed>
     */
    public function validationRules(): array
    {
        if (! $this->enabled()) {
            return ['nullable', 'string'];
        }

        return [
            'required',
            'string',
            function (string $attribute, mixed $value, Closure $fail) {
                if (! $this->verify((string) $value)) {
                    $fail($this->failureMessage());
                }
            },
        ];
    }

    /**
     * Rules for the honeypot input. Real people never see the field, so any
     * value at all marks the submission as automated.
     *
     * @return array<int, string>
     */
    public function honeypotRules(): array
    {
        return ['prohibited'];
    }

    /**
     * @return array<string, string>
     */
    public function honeypotMessages(): array
    {
        return ['website.prohibited' => 'Submission rejected.'];
    }

    /**
     * Generate a new image-captcha code and store it in session.
     */
    public function generate(): string
    {
        $code = strtoupper(Str::random(5));
        session([self::SESSION_KEY => $code]);

        return $code;
    }

    /**
     * Verify a submitted image-captcha answer and clear the code from session.
     * Always clears on call so a code cannot be reused.
     */
    protected function verifyImageCode(string $input): bool
    {
        $expected = session(self::SESSION_KEY);

        session()->forget([self::SESSION_KEY, self::TOKEN_KEY]);

        if (! $expected) {
            return false;
        }

        return strtoupper(trim($input)) === strtoupper($expected);
    }

    /**
     * Render the CAPTCHA as a PNG image response.
     *
     * The optional $token makes image generation deterministic for a given
     * request URL: fetching the same token (e.g. a duplicated browser request)
     * returns the SAME code already stored in session instead of silently
     * regenerating it. A new token (the "Refresh" button) produces a fresh
     * code. This keeps the displayed code and the session code in lockstep.
     */
    public function imageResponse(?string $token = null): Response
    {
        if ($token !== null && $token !== '' && session(self::TOKEN_KEY) === $token && session(self::SESSION_KEY)) {
            $code = (string) session(self::SESSION_KEY);
        } else {
            $code = $this->generate();

            if ($token !== null && $token !== '') {
                session([self::TOKEN_KEY => $token]);
            }
        }

        $width = 160;
        $height = 52;

        $image = imagecreatetruecolor($width, $height);

        // Background
        $bg = imagecolorallocate($image, 248, 247, 242);
        imagefill($image, 0, 0, $bg);

        // Noise lines
        for ($i = 0; $i < 6; $i++) {
            $c = imagecolorallocate($image, rand(180, 215), rand(180, 215), rand(180, 215));
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $c);
        }

        // Noise dots
        for ($i = 0; $i < 60; $i++) {
            $c = imagecolorallocate($image, rand(195, 230), rand(195, 230), rand(195, 230));
            imagesetpixel($image, rand(0, $width), rand(0, $height), $c);
        }

        // Draw each character at a slight random vertical offset and colour
        $chars = str_split($code);
        $charCount = count($chars);
        $slotWidth = (int) ($width / ($charCount + 1));

        foreach ($chars as $i => $char) {
            $color = imagecolorallocate($image, rand(25, 80), rand(25, 80), rand(25, 80));

            $x = (int) (($i + 0.5) * $slotWidth) + rand(-2, 2);
            $y = rand(8, $height - 22);

            // Font 5 is the largest built-in GD font (9x15 px)
            imagestring($image, 5, $x, $y, $char, $color);
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
