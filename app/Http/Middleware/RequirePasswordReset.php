<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Admin\ImpersonationController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Holds a flagged user on the forced-password-reset screen until they choose a
 * new password. Used for accounts found to be on a known/default credential.
 */
class RequirePasswordReset
{
    /**
     * Routes a flagged user may still reach, so they can complete the reset
     * (or leave) without being redirected in a loop.
     */
    private const ALLOWED_ROUTES = [
        'password.forced',
        'password.forced.update',
        'logout',
        'captcha.image',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->mustResetPassword()) {
            return $next($request);
        }

        // An impersonating admin must not be trapped on — or able to complete —
        // the target's forced password reset.
        if ($request->session()->has(ImpersonationController::IMPERSONATOR_KEY)) {
            return $next($request);
        }

        if ($request->routeIs(self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'You must choose a new password before continuing.');
        }

        return redirect()->route('password.forced');
    }
}
