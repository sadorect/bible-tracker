<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForcedPasswordResetController extends Controller
{
    public function show(Request $request): RedirectResponse|View
    {
        if (! $request->user()->mustResetPassword()) {
            return redirect()->route('dashboard');
        }

        return view('auth.forced-password-reset');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();

        // Stops the user from "resetting" straight back to the known credential.
        if (Hash::check($validated['password'], $user->password)) {
            return back()->withErrors([
                'password' => 'Please choose a password you have not used on this account before.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'force_password_reset' => false,
        ])->save();

        // Invalidate any other session riding the old credential.
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('status', 'Your password has been updated. Thanks for keeping your account secure.');
    }
}
