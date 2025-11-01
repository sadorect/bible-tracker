<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        // Generate simple math captcha (password reset request)
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        session([
            'captcha_pwreq_a' => $a,
            'captcha_pwreq_b' => $b,
            'captcha_pwreq_sum' => $a + $b,
        ]);

        return view('auth.forgot-password', [
            'captchaA' => $a,
            'captchaB' => $b,
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate captcha first
        $request->validate([
            'captcha_answer' => ['required','integer'],
        ], [
            'captcha_answer.required' => 'Please answer the math question.',
        ]);

        $answer = (int) $request->input('captcha_answer');
        $expected = (int) session('captcha_pwreq_sum');
        if ($answer !== $expected) {
            return back()->withErrors(['captcha_answer' => 'Incorrect answer to the math question.'])->withInput();
        }

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
