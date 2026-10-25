<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * "Login as" — lets a super admin take on another account's session to see the
 * app exactly as that person does.
 *
 * The original account id is parked in the session under IMPERSONATOR_KEY. Its
 * presence is what makes the session an impersonation: the layouts render a
 * banner from it, and it is the only way back to the real account.
 */
class ImpersonationController extends Controller
{
    public const IMPERSONATOR_KEY = 'impersonator_id';

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function start(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('impersonate-users');

        if ($request->session()->has(self::IMPERSONATOR_KEY)) {
            return back()->with('error', 'You are already signed in as someone else. Return to your own account first.');
        }

        $actor = $request->user();

        if ($user->is($actor)) {
            return back()->with('error', 'You are already signed in as yourself.');
        }

        $this->auditLogger->log(
            'users.impersonation_started',
            $actor,
            $user,
            [
                'target_role' => $user->role,
                'target_hierarchy' => $user->hierarchy?->displayPath(),
            ],
            "Started viewing the app as {$user->name}.",
        );

        // Auth::login() migrates the session, which keeps session data, so the
        // marker survives the switch. Set it after the swap to be explicit.
        Auth::login($user);

        $request->session()->put(self::IMPERSONATOR_KEY, $actor->getKey());

        return redirect()->route('dashboard')
            ->with('info', "You are now viewing the app as {$user->name}.");
    }

    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull(self::IMPERSONATOR_KEY);

        if (! $impersonatorId) {
            return redirect()->route('dashboard');
        }

        $impersonator = User::find($impersonatorId);

        if (! $impersonator) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your original account could not be restored. Please sign in again.');
        }

        $impersonated = $request->user();

        Auth::login($impersonator);

        $this->auditLogger->log(
            'users.impersonation_stopped',
            $impersonator,
            $impersonated,
            [],
            $impersonated
                ? "Stopped viewing the app as {$impersonated->name}."
                : 'Stopped an impersonation session.',
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'You are back on your own account.');
    }
}
