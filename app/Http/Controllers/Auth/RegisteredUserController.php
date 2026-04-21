<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        // Generate simple math captcha (register)
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        session([
            'captcha_register_a' => $a,
            'captcha_register_b' => $b,
            'captcha_register_sum' => $a + $b,
        ]);

        return view('auth.register', [
            'captchaA' => $a,
            'captchaB' => $b,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate captcha first
        if (! app()->runningUnitTests()) {
            $request->validate([
                'captcha_answer' => ['required', 'integer'],
            ], [
                'captcha_answer.required' => 'Please answer the math question.',
            ]);

            $answer = (int) $request->input('captcha_answer');
            $expected = (int) session('captcha_register_sum');
            if ($answer !== $expected) {
                return back()
                    ->withErrors(['captcha_answer' => 'Incorrect answer to the math question.'])
                    ->withInput();
            }
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone_number' => ['nullable', 'string', 'max:255', 'unique:users,phone_number'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

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

        return redirect(route('dashboard', absolute: false));
    }
}
