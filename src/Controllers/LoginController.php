<?php

namespace Modules\Opx\Users\Controllers;

use Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Opx\Users\Traits\Redirects;
use Modules\Opx\Users\Traits\ThrottlesLogin;
use Modules\Opx\Users\Events\UserAuthenticated;
use Modules\Opx\Users\Exceptions\InvalidCredentialsException;
use Modules\Opx\Users\Exceptions\LockoutException;
use Modules\Opx\Users\Exceptions\LoginFailureException;
use Modules\Opx\Users\Models\User;
use Modules\Opx\Users\Traits\Authenticate;


class LoginController extends Controller
{
    use Authenticate,
        ThrottlesLogin,
        Redirects;

    protected $codes = [
        'success' => 200,
        'invalid_credentials' => 400,
        'login_failure' => 420,
        'throttle_login' => 421,
    ];

    /**
     * Login user through regular http request.
     *
     * @param Request $request
     *
     * @return  RedirectResponse
     */
    public function login(Request $request): RedirectResponse
    {
        try {
            $this->performLogin($request);

        } catch (InvalidCredentialsException|LockoutException|LoginFailureException $exception) {

            return back($exception->getCode())
                ->withInput($exception->getCredentials())
                ->withErrors($exception->getErrors())
                ->with(['message' => $exception->getMessage()]);
        }

        return response()->redirectTo(
            $this->redirectTo('after_login'),
            $this->codes['success']
        )->with(['message' => trans('opx_users::auth.login_success')]);
    }

    /**
     * Login user through API.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     */
    public function loginApi(Request $request): JsonResponse
    {
        try {
            $this->performLogin($request);

        } catch (InvalidCredentialsException|LockoutException|LoginFailureException $exception) {

            return response()->json($exception->toArray(), $exception->getCode());
        }

        return response()->json([
            'message' => trans('opx_users::auth.login_success'),
            'redirect' => $this->redirectTo('after_login'),
        ], $this->codes['success']);
    }

    /**
     * Perform login action.
     *
     * @param Request $request
     *
     * @return  void
     *
     * @throws InvalidCredentialsException
     * @throws LockoutException
     * @throws LoginFailureException
     */
    protected function performLogin(Request $request): void
    {
        // Get credentials from request
        // throws exception on failure
        $credentials = $this->getValidatedCredentials($request);
        $ip = $request->ip();

        // Check for maximum login attempts exceeded
        // throws exception on failure
        $this->checkForMaxAttempts($credentials, $ip);

        $loggedIn = $this->attemptToLogin($credentials, $request->has('remember'));

        if (!$loggedIn) {
            $this->incrementLoginAttempts($credentials, $ip);

            throw new LoginFailureException(
                trans('opx_users::auth.login_failed'),
                [],
                $credentials,
                $this->codes['login_failure']
            );
        }

        $this->clearLoginAttempts($credentials, $ip);
    }

    /**
     * Get credentials from request and validate it.
     *
     * @param Request $request
     *
     * @return  array
     *
     * @throws  InvalidCredentialsException
     */
    protected function getValidatedCredentials(Request $request): array
    {
        // Get credentials from request
        $credentials = $this->credentials($request);

        // Validate credentials
        $errors = $this->validateCredentials($credentials);

        if ($errors) {
            throw new InvalidCredentialsException(
                trans('opx_users::auth.login_validation_error'),
                $errors->messages(),
                $credentials,
                $this->codes['invalid_credentials']
            );
        }

        return $credentials;
    }

    /**
     * Check for max login attempts exceeded.
     *
     * @param array $credentials
     * @param string $ip
     *
     * @throws LockoutException
     */
    protected function checkForMaxAttempts(array $credentials, string $ip): void
    {
        if ($this->hasTooManyLoginAttempts($credentials, $ip)) {

            $this->fireLockoutEvent($credentials, $ip);

            $seconds = $this->lockoutSeconds($credentials, $ip);

            throw new LockoutException(
                trans('opx_users::auth.login_throttle', ['seconds' => $seconds]),
                [],
                $this->codes['throttle_login'],
                $seconds
            );
        }
    }

    /**
     * Attempt lo login user.
     *
     * @param array $credentials
     * @param bool $remember
     *
     * @return  bool
     */
    protected function attemptToLogin(array $credentials, bool $remember): bool
    {
        $success = Auth::guard('user')->attempt($credentials, $remember);

        if ($success) {

            /** @var User $user */
            $user = Auth::guard('user')->user();

            $user->updateLastLogin();

            event(new UserAuthenticated($user));
        }

        return $success;
    }

    /**
     * Credentials validation rules.
     *
     * @return  array
     */
    protected function validationRules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
        ];
    }
}