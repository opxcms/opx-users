<?php

namespace Modules\Opx\Users\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;

class Authenticate
{
    /**
     * The authentication factory instance.
     *
     * @var Auth
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  Auth  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     *
     * @return mixed
     *
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next)
    {
        $this->authenticate();

        return $next($request);
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @return void
     *
     * @throws AuthenticationException
     */
    protected function authenticate() :void
    {
        if ($this->auth->guard('user')->check()) {
            $this->auth->shouldUse('user');
        } else {
            throw new AuthenticationException(
                'Unauthenticated.', ['user'], $this->redirectTo()
            );
        }
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @return string
     */
    protected function redirectTo() :string
    {
        return '/login';
    }
}
