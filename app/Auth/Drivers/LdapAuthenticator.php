<?php

namespace App\Auth\Drivers;

use App\Auth\Contracts\Authenticator;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/** Active Directory / LDAP bind. Requires the php-ldap extension. */
class LdapAuthenticator implements Authenticator
{
    public function attempt(string $login, string $password): ?User
    {
        if (! function_exists('ldap_connect') || $password === '') {
            return null;
        }
        $cfg = config('pulse.auth.ldap');
        $username = strtolower(trim(explode('@', $login)[0]));
        $username = preg_replace('/[^a-z0-9._-]/', '', $username);

        $conn = @ldap_connect($cfg['host'].':'.$cfg['port']);
        if (! $conn) return null;
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        if ($cfg['use_tls'] && ! @ldap_start_tls($conn)) {
            Log::warning('Pulse LDAP: STARTTLS failed');
            return null;
        }

        $bindDn = str_replace('{username}', $username, $cfg['bind_format']);
        if (! @ldap_bind($conn, $bindDn, $password)) {
            return null;
        }

        // Pull display name + mail so the local record stays current.
        $name = $username;
        $email = $username.'@'.$cfg['email_domain'];
        $title = null;
        $search = @ldap_search($conn, $cfg['base_dn'], '(sAMAccountName='.ldap_escape($username, '', LDAP_ESCAPE_FILTER).')',
            ['displayname', 'mail', 'title']);
        if ($search && ($entries = ldap_get_entries($conn, $search)) && $entries['count'] > 0) {
            $e = $entries[0];
            $name = $e['displayname'][0] ?? $name;
            $email = strtolower($e['mail'][0] ?? $email);
            $title = $e['title'][0] ?? null;
        }
        ldap_unbind($conn);

        $user = User::where('email', $email)->first();
        if (! $user) {
            $role = config('pulse.auth.auto_provision_role');
            if (! $role) return null;
            $user = User::create(['name' => $name, 'email' => $email, 'title' => $title,
                'role' => $role, 'auth_source' => 'ldap', 'active' => true]);
        } else {
            $user->fill(['name' => $name, 'title' => $title ?? $user->title])->save();
        }

        return $user;
    }
}
