<?php

return [
    'tables' => ['users', 'users_details', 'users_password_resets', 'users_email_confirmations', 'users_phone_confirmations'],

    // Global settings
    'min_password_length' => 6,

    // login max attempts and throttle options
    'max_attempts' => 5,
    'decay_minutes' => 1,
    'token_decay_seconds' => 30,

    // redirects
    'redirects' => [
        'after_login' => '/',
        'after_logout' => '/',
        'after_register' => ['route' => 'opx_users::login'],
        'after_reset' => ['route' => 'opx_users::login'],
    ],

    // login conditions
    'login_settings' => [
        'enable_not_activated' => true,
        'activate_on_login' => true,
        'enable_not_confirmed_email' => false,
        'send_confirmation_email' => true,
    ],

    // register conditions
    'register_settings' => [
        'registration_enabled' => true,
    ],

    // in minutes
    'email_confirm_token_ttl' => 1440,
    'reset_token_ttl' => 1440,
];