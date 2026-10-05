<?php

namespace App\Auth\Contracts;

use App\Models\User;

/**
 * A pluggable sign-in strategy. Return the local User on success, null on failure.
 * Drivers verify credentials however they like (DB hash, LDAP bind, an SSO API...)
 * and may create / sync the local user record.
 */
interface Authenticator
{
    public function attempt(string $login, string $password): ?User;
}
