<?php

namespace Modules\Opx\Users\Traits;

trait ResponseCodes
{
    protected $codes = [
        'success' => 200,
        'invalid_credentials' => 400,
        'login_failure' => 401,
        'throttle_login' => 429,
        'throttle_reset' => 400,
        'user_is_blocked' => 401,
        'token_mismatch' => 400,
        'token_expired' => 400,
        'registration_disabled' => 400,
        'user_exists' => 400,
        'user_not_exists' => 400,
        'already_logged_in' => 400,
        'not_logged_in' => 400,
        'email_not_confirmed' => 400,
    ];
}