<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CAPTCHA
    |--------------------------------------------------------------------------
    |
    | Anti-abuse challenge shown on the public auth forms.
    |
    | Left unset, "enabled" is on everywhere except the test suite, and the
    | driver resolves to turnstile when both Turnstile keys are present and to
    | the image fallback otherwise. That way an environment with no keys still
    | gets a working, solvable form rather than an unsolvable one.
    |
    */

    'captcha' => [
        'enabled' => env('CAPTCHA_ENABLED'),

        /*
         * "turnstile" — Cloudflare Turnstile; invisible for most visitors,
         *               accessible, and actually adversarial to solvers.
         * "image"     — in-house GD image captcha, used as the fallback when
         *               no Turnstile keys are configured.
         */
        'driver' => env('CAPTCHA_DRIVER'),

        'turnstile' => [
            'site_key' => env('TURNSTILE_SITE_KEY'),
            'secret_key' => env('TURNSTILE_SECRET_KEY'),
            'verify_url' => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),
            'timeout' => (int) env('TURNSTILE_TIMEOUT', 5),
        ],
    ],

];
