<?php

namespace App\Auth;

use App\Auth\Contracts\Authenticator;
use App\Auth\Drivers\DatabaseAuthenticator;
use App\Models\User;
use InvalidArgumentException;

class AuthenticatorManager
{
    public function driver(?string $name = null): Authenticator
    {
        $name ??= config('pulse.auth.driver');
        $class = config("pulse.auth.drivers.$name");
        if (! $class || ! is_a($class, Authenticator::class, true)) {
            throw new InvalidArgumentException("Unknown Pulse auth driver [$name].");
        }

        return app($class);
    }

    /** Try the configured driver; admins can always fall back to their local password. */
    public function attempt(string $login, string $password): ?User
    {
        $user = $this->driver()->attempt($login, $password);

        if (! $user && config('pulse.auth.driver') !== 'database' && config('pulse.auth.local_fallback_for_admins')) {
            $local = app(DatabaseAuthenticator::class)->attempt($login, $password);
            $user = $local?->role === 'admin' ? $local : null;
        }

        return $user && $user->active ? $user : null;
    }
}
