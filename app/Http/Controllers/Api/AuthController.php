<?php

namespace App\Http\Controllers\Api;

use App\Auth\AuthenticatorManager;
use App\Http\Controllers\Controller;
use App\Support\Pulse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request, AuthenticatorManager $auth)
    {
        $data = $request->validate(['login' => 'required|string|max:180', 'password' => 'required|string', 'remember' => 'boolean']);
        $key = 'login:'.strtolower($data['login']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['login' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }
        $user = $auth->attempt($data['login'], $data['password']);
        if (! $user) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['login' => 'Those details did not match an active account.']);
        }
        RateLimiter::clear($key);
        Auth::login($user, $data['remember'] ?? false);
        $request->session()->regenerate();

        return $this->me($request);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
            'lookups' => Pulse::lookups(),
            'auth_driver' => config('pulse.auth.driver'),
            'week' => Pulse::weekStart()->toDateString(),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function password(Request $request)
    {
        $data = $request->validate(['current' => 'required|current_password', 'password' => 'required|string|min:8|confirmed']);
        $request->user()->update(['password' => $data['password']]);

        return response()->noContent();
    }
}
