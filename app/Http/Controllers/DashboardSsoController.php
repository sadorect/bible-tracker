<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Accepts a signed handoff token from dashboard.sadorect.com.
 *
 * Installed and maintained by AppDash. Every failure path is a plain 404: a
 * probing attacker learns nothing about which step rejected them.
 */
class DashboardSsoController extends Controller
{
    public function __invoke(Request $request)
    {
        $secret = config('dashboard.secret');

        // 1. No secret configured -> the feature does not exist here.
        if (! is_string($secret) || $secret === '') {
            throw new NotFoundHttpException;
        }

        $token = (string) $request->query('t', '');

        if (! str_contains($token, '.')) {
            return $this->deny($request, 'malformed token');
        }

        [$encoded, $signature] = explode('.', $token, 2);

        // 2. Signature, compared in constant time.
        $expected = $this->base64UrlEncode(
            hash_hmac('sha256', $encoded, $secret, binary: true)
        );

        if (! hash_equals($expected, $signature)) {
            return $this->deny($request, 'bad signature');
        }

        $payload = json_decode($this->base64UrlDecode($encoded), true);

        if (! is_array($payload) || ! isset($payload['sub'], $payload['exp'], $payload['iat'], $payload['jti'])) {
            return $this->deny($request, 'incomplete payload');
        }

        // 3. Freshness. Both bounds matter: a far-future iat would otherwise
        //    let a leaked token stay valid indefinitely.
        $now = time();

        if ($payload['exp'] < $now) {
            return $this->deny($request, 'expired', $payload);
        }

        if ($payload['iat'] > $now + 30 || $payload['iat'] < $now - config('dashboard.max_age')) {
            return $this->deny($request, 'iat out of range', $payload);
        }

        // 4. Single use. Cache::add is atomic, so a replay -- even a
        //    simultaneous one -- loses the race and is refused.
        if (! Cache::add('dash_sso:'.$payload['jti'], 1, config('dashboard.max_age') + 60)) {
            return $this->deny($request, 'token replayed', $payload);
        }

        // 5. The user must already exist. We never provision.
        $user = $this->findUser($payload['sub']);

        if (! $user) {
            return $this->deny($request, 'no such user', $payload);
        }

        // 6. The user must already be an admin here. We never elevate.
        if (! $this->passesAdminCheck($user)) {
            return $this->deny($request, 'user is not an admin here', $payload);
        }

        Auth::login($user);
        $request->session()->regenerate();

        Log::info('Dashboard SSO accepted', [
            'sub' => $payload['sub'],
            'jti' => $payload['jti'],
            'ip' => $request->ip(),
        ]);

        return redirect($this->landingPath($payload));
    }

    /**
     * The dashboard says where to land (its per-app admin URL, editable there).
     * It arrives inside the signed payload, so it cannot be tampered with in
     * transit; it is still confined to a local path so a mis-set value can
     * never turn this into an open redirect.
     */
    private function landingPath(array $payload): string
    {
        $to = $payload['to'] ?? null;

        if (is_string($to)
            && str_starts_with($to, '/')
            && ! str_starts_with($to, '//')
            && ! str_contains($to, '\\')
            && ! preg_match('/[\x00-\x1F\x7F]/', $to)) {
            return $to;
        }

        return config('dashboard.redirect_to');
    }

    private function findUser(string $email)
    {
        $model = config('auth.providers.users.model');

        return $model::query()->where('email', $email)->first();
    }

    /**
     * Mirrors whatever check this app already applies to its admin routes.
     * Anything unrecognised denies -- the switch fails closed by default.
     */
    private function passesAdminCheck($user): bool
    {
        $check = (string) config('dashboard.admin_check', '');

        if ($check === '') {
            return false;
        }

        [$strategy, $argument] = array_pad(explode(':', $check, 2), 2, '');

        return match ($strategy) {
            'filament' => $this->filamentCheck($user, $argument),
            'method' => method_exists($user, $argument) && (bool) $user->{$argument}(),
            'attribute' => (bool) ($user->{$argument} ?? false),
            'role' => method_exists($user, 'hasAnyRole')
                && $user->hasAnyRole(array_map('trim', explode(',', $argument))),
            'permission' => method_exists($user, 'can') && $user->can($argument),
            'gate' => Gate::forUser($user)->allows($argument),
            default => false,
        };
    }

    private function filamentCheck($user, string $panelId): bool
    {
        if (! method_exists($user, 'canAccessPanel') || ! class_exists(\Filament\Facades\Filament::class)) {
            return false;
        }

        try {
            return $user->canAccessPanel(\Filament\Facades\Filament::getPanel($panelId));
        } catch (\Throwable $e) {
            Log::warning('Dashboard SSO: Filament panel check failed: '.$e->getMessage());

            return false;
        }
    }

    private function deny(Request $request, string $reason, ?array $payload = null)
    {
        Log::warning('Dashboard SSO refused', [
            'reason' => $reason,
            'ip' => $request->ip(),
            'sub' => $payload['sub'] ?? null,
            'jti' => $payload['jti'] ?? null,
        ]);

        throw new NotFoundHttpException;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
