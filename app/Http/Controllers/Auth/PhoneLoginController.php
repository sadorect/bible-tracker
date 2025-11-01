<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PhoneLoginController extends Controller
{
    public function showLoginForm()
    {
        // Generate simple math captcha (phone login)
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        session([
            'captcha_phone_a' => $a,
            'captcha_phone_b' => $b,
            'captcha_phone_sum' => $a + $b,
        ]);

        return view('auth.phone-login', [
            'captchaA' => $a,
            'captchaB' => $b,
        ]);
    }

    public function login(Request $request)
    {
        // Validate captcha first
        $request->validate([
            'captcha_answer' => ['required','integer'],
        ], [
            'captcha_answer.required' => 'Please answer the math question.',
        ]);

        $answer = (int) $request->input('captcha_answer');
        $expected = (int) session('captcha_phone_sum');
        if ($answer !== $expected) {
            return back()->withErrors(['captcha_answer' => 'Incorrect answer to the math question.'])->withInput();
        }

        $validated = $request->validate([
            'phone_number' => 'required|string|exists:users,phone_number',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($validated)) {
            $request->session()->regenerate();
            // clear captcha
            session()->forget(['captcha_phone_a', 'captcha_phone_b', 'captcha_phone_sum']);
            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'phone_number' => 'The provided credentials do not match our records.',
        ]);
    }
}
