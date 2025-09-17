<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function loginPage()
    {
        return view('masuk');
    }

    public function login(Request $request)
    {
        // Throttle key berbasis IP + username
        $throttleKey = Str::lower($request->input('username')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'username' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ])->onlyInput('username');
        }

        $credentials = // Contoh di LoginController
            $request->validate([
                'username' => ['required', 'string', 'min:6'],
                'password' => ['required', 'string', 'min:8'],
            ], [
                'username.required' => 'Username wajib diisi.',
                'username.min'      => 'Username minimal 6 karakter.',
                'password.required' => 'Password wajib diisi.',
                'password.min'      => 'Password minimal 8 karakter.',
            ]);


        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            return redirect()->intended('/dashboard')
                ->with('success', 'Berhasil masuk. Selamat datang!');
        }

        RateLimiter::hit($throttleKey, 60); // reset hit setelah 60 detik
        return back()
            ->with('error', 'Username atau password salah.')
            ->onlyInput('username');
    }

    public function keluar(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar.');
    }
}
