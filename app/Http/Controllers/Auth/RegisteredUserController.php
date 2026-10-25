<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReadingPlanInviteController;
use App\Models\User;
use App\Services\CaptchaService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $captcha = app(CaptchaService::class);

        $request->validate([
            'website' => $captcha->honeypotRules(),
            'captcha' => $captcha->validationRules(),
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone_number' => ['nullable', 'string', 'max:255', 'unique:users,phone_number'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], $captcha->honeypotMessages());

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->filled('phone_number') ? $request->phone_number : null,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Invalidate captcha after successful registration
        session()->forget(['captcha_register_a', 'captcha_register_b', 'captcha_register_sum']);

        if ($pendingInviteRedirect = ReadingPlanInviteController::pendingInviteRedirectUrl($request)) {
            return redirect()->to($pendingInviteRedirect);
        }

        return redirect(route('dashboard', absolute: false));
    }
}
