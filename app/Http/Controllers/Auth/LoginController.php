<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Simple session login using Laravel's built-in users table.
 * No registration page: users are created by the administrator.
 */
class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            $request->session()->regenerate();
            flash_toast('success', 'Welcome back, ' . Auth::user()->name . '!');
            return redirect()->intended(route('dashboard'));
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => 'The email or password is incorrect, or the account is inactive.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
