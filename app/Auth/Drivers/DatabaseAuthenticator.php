<?php

namespace App\Auth\Drivers;

use App\Auth\Contracts\Authenticator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseAuthenticator implements Authenticator
{
    public function attempt(string $login, string $password): ?User
    {
        $user = User::where('email', strtolower(trim($login)))->first();

        return $user && $user->password && Hash::check($password, $user->password) ? $user : null;
    }
}
