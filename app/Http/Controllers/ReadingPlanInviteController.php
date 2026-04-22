<?php

namespace App\Http\Controllers;

use App\Models\ReadingPlanInvite;
use App\Services\ReadingPlanParticipationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReadingPlanInviteController extends Controller
{
    public const PENDING_SESSION_KEY = 'pending_reading_plan_invite_token';

    public function __construct(
        private readonly ReadingPlanParticipationService $participationService,
    ) {
    }

    public function show(Request $request, string $token): View
    {
        $invite = $this->resolveInvite($token);
        $user = $request->user();

        return view('reading-plan-invites.show', [
            'layoutComponent' => $user ? ($user->isAdmin() ? 'admin-layout' : 'app-layout') : 'guest-layout',
            'invite' => $invite,
            'readingPlan' => $invite->readingPlan()->with('trainingResources')->first(),
            'user' => $user,
            'isUsable' => $invite->isUsable(),
            'isExpired' => $invite->isExpired(),
            'isRevoked' => $invite->isRevoked(),
            'existingParticipationCount' => $user
                ? $user->readingPlanParticipations()->where('reading_plan_id', $invite->reading_plan_id)->count()
                : 0,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invite = $this->resolveInvite($token);
        $user = $request->user();

        abort_unless($user, 403);

        if (! $invite->isUsable()) {
            return redirect()->route('reading-plan-invites.show', $invite->token)
                ->with('error', 'This enrollment link is no longer available.');
        }

        $this->participationService->startNewParticipation(
            $user,
            $invite->readingPlan,
            $invite,
            \App\Models\ReadingPlanParticipation::SOURCE_INVITE,
        );

        $request->session()->forget(self::PENDING_SESSION_KEY);

        return redirect()->route('reading-plans.show', $invite->readingPlan)
            ->with('success', 'Enrollment confirmed. A fresh participation cycle has been started on your profile.');
    }

    public function beginLogin(Request $request, string $token): RedirectResponse
    {
        $invite = $this->resolveInvite($token);
        $request->session()->put(self::PENDING_SESSION_KEY, $invite->token);

        return redirect()->route('login');
    }

    public function beginRegisterFresh(Request $request, string $token): RedirectResponse
    {
        $invite = $this->resolveInvite($token);
        $request->session()->put(self::PENDING_SESSION_KEY, $invite->token);

        if (Auth::check()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put(self::PENDING_SESSION_KEY, $invite->token);
        }

        return redirect()->route('register');
    }

    public static function pendingInviteRedirectUrl(Request $request): ?string
    {
        $token = $request->session()->get(self::PENDING_SESSION_KEY);

        if (! $token) {
            return null;
        }

        return route('reading-plan-invites.show', $token);
    }

    private function resolveInvite(string $token): ReadingPlanInvite
    {
        return ReadingPlanInvite::query()
            ->where('token', $token)
            ->with('readingPlan')
            ->firstOrFail();
    }
}
