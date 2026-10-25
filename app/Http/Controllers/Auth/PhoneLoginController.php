<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReadingPlanInviteController;
use App\Services\CaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhoneLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.phone-login');
    }

    public function login(Request $request)
    {
        $captcha = app(CaptchaService::class);

        $request->validate([
            'website' => $captcha->honeypotRules(),
            'captcha' => $captcha->validationRules(),
        ], $captcha->honeypotMessages());

        $validated = $request->validate([
            'phone_number' => 'required|string|exists:users,phone_number',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($validated)) {
            $request->session()->regenerate();

            if ($pendingInviteRedirect = ReadingPlanInviteController::pendingInviteRedirectUrl($request)) {
                return redirect()->to($pendingInviteRedirect);
            }

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'phone_number' => 'The provided credentials do not match our records.',
        ]);
    }
}
