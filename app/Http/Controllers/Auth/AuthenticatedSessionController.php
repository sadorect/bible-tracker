<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        // Generate simple math captcha (login)
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        session([
            'captcha_login_a' => $a,
            'captcha_login_b' => $b,
            'captcha_login_sum' => $a + $b,
        ]);

        return view('auth.login', [
            'captchaA' => $a,
            'captchaB' => $b,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Validate captcha before authentication
        $answer = (int) $request->input('captcha_answer');
        $expected = (int) session('captcha_login_sum');
        if ($expected === 0 && $expected !== $answer) {
            // ensure expected is set; zero would be invalid here since a,b>=1
            return back()
                ->withErrors(['captcha_answer' => 'Please answer the math question.'])
                ->withInput();
        }
        if ($answer !== $expected) {
            return back()
                ->withErrors(['captcha_answer' => 'Incorrect answer to the math question.'])
                ->withInput();
        }

        $request->authenticate();

        $request->session()->regenerate();

        // Invalidate captcha after successful login attempt
        session()->forget(['captcha_login_a', 'captcha_login_b', 'captcha_login_sum']);

        if (auth()->user()->isAdmin()) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
