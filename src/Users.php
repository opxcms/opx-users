<?php

namespace Modules\Opx\Users;

use Core\Foundation\Module\BaseModule;
use Illuminate\Support\Facades\Auth;
use Modules\Opx\Users\Middleware\Authenticate;
use Modules\Opx\Users\Models\User;

class Users extends BaseModule
{
    /** @var string  Module name */
    protected $name = 'opx_users';

    /** @var string  Module path */
    protected $path = __DIR__;

    protected $routeMiddleware = [
        'auth_user' => Authenticate::class,
    ];

    /**
     * Check if user is logged in.
     *
     * @return  bool
     */
    public function check(): bool
    {
        return Auth::guard('user')->check();
    }

    /**
     * Get user.
     *
     * @return  User
     */
    public function user(): User
    {
        return Auth::guard('user')->user();
    }
}
