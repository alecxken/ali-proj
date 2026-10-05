<?php

return [
    'org_name' => env('PULSE_ORG_NAME', 'Retail Digital Banking'),
    'report_title' => env('PULSE_REPORT_TITLE', 'Weekly Project Progress Report'),
    'brand_color' => env('PULSE_BRAND_COLOR', '#0B4F6C'),

    /*
    | Authentication is pluggable. Pick a driver with PULSE_AUTH_DRIVER.
    | To add your own (Azure AD, Okta, a bank SSO API...), write a class that implements
    | App\Auth\Contracts\Authenticator and register it under 'drivers' below.
    */
    'auth' => [
        'driver' => env('PULSE_AUTH_DRIVER', 'database'),

        'drivers' => [
            'database' => App\Auth\Drivers\DatabaseAuthenticator::class,
            'ldap' => App\Auth\Drivers\LdapAuthenticator::class,
        ],

        // When an external directory accepts a login for someone not yet in Pulse,
        // create them with this role (set to null to require an admin to add them first).
        'auto_provision_role' => env('PULSE_AUTO_PROVISION_ROLE', 'viewer'),

        // Local admin accounts can always sign in with their password, even when an
        // external driver is active — your break-glass route if the directory is down.
        'local_fallback_for_admins' => env('PULSE_LOCAL_ADMIN_FALLBACK', true),

        'ldap' => [
            'host' => env('LDAP_HOST', 'ldap://dc.example.local'),
            'port' => (int) env('LDAP_PORT', 389),
            'use_tls' => (bool) env('LDAP_TLS', false),
            // How the login name is turned into a bind DN / UPN. {username} is replaced.
            'bind_format' => env('LDAP_BIND_FORMAT', '{username}@example.local'),
            'base_dn' => env('LDAP_BASE_DN', 'DC=example,DC=local'),
            'email_domain' => env('LDAP_EMAIL_DOMAIN', 'example.com'),
        ],
    ],

    'reports' => [
        // Python interpreter with reportlab + python-pptx installed (see reports/requirements.txt)
        'python' => env('PULSE_PYTHON', 'python3'),
        'script' => base_path('reports/generate_report.py'),
        'disk_path' => storage_path('app/reports'),
        'timeout' => 120,
    ],
];
