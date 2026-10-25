<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\CaptchaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CaptchaController extends Controller
{
    public function __construct(
        private readonly CaptchaService $captcha,
    ) {}

    /**
     * Serve the image-CAPTCHA fallback.
     *
     * Only reached when Turnstile is unconfigured (see CaptchaService::driver()).
     * Traffic here in production means the Turnstile keys are missing.
     */
    public function image(Request $request): Response
    {
        return $this->captcha->imageResponse((string) $request->query('v'));
    }
}
