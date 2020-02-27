<?php

namespace Modules\Opx\Users\Controllers;

use Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Opx\Users\Events\UserActivated;
use Modules\Opx\Users\Exceptions\BaseUsersException;
use Modules\Opx\Users\Exceptions\EmailNotConfirmedException;
use Modules\Opx\Users\Exceptions\UserBlockedException;
use Modules\Opx\Users\Exceptions\UserNotActivatedException;
use Modules\Opx\Users\OpxUsers;
use Modules\Opx\Users\Traits\EmailConfirmation;
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
        Redirects,
        EmailConfirmation;

    protected $codes = [
        'success' => 200,
        'invalid_credentials' => 400,
        'login_failure' => 429,
        'throttle_login' => 429,
        'user_is_blocked' => 429,
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

        } catch (BaseUsersException $exception) {

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

        } catch (BaseUsersException $exception) {

            return response()->json($exception->toArray(), $exception->getCode());
        }

        return response()->json([
            'message' => trans('opx_users::auth.login_success'),
            'redirect' => $this->redirectTo('after_login'),
            'token' => csrf_token(),
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
     * @throws UserBlockedException
     * @throws UserNotActivatedException
     */
    protected function performLogin(Request $request): void
    {
        // Get credentials from request
        // throws InvalidCredentialsException exception on failure
        $credentials = $this->getValidatedCredentials($request);
        $ip = $request->ip();

        // Check for maximum login attempts exceeded
        // throws LockoutException exception on failure
        $this->checkForMaxAttempts($credentials, $ip);

        // Get user attempting to authenticate
        $user = $this->getAuthenticatingUser($credentials);

        if ($user === null) {

            $this->incrementLoginAttempts($credentials, $ip);

            throw new LoginFailureException(
                trans('opx_users::auth.login_failed'),
                [],
                $credentials,
                $this->codes['login_failure']
            );
        }

        // Check user account blocked
        // throws UserBlockedException exception on failure
        $this->checkUserIsBlocked($user, $credentials);

        // Check user email verified
        // TODO check email conditions

        // Check user account activated
        // throws UserNotActivatedException exception on failure
        $this->checkUserIsActivated($user, $credentials);

        // Finally, log user in using current guard.
        $this->loginUser($user, $request->has('remember'));

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
     * Find user trying to authenticate.
     *
     * @param array $credentials
     *
     * @return  User|null
     */
    protected function getAuthenticatingUser(array $credentials): ?User
    {
        $success = Auth::guard('user')->once($credentials);

        if (!$success) {
            return null;
        }

        return Auth::guard('user')->user();
    }

    /**
     * Check if user is blocked.
     *
     * @param User $user
     * @param array $credentials
     *
     * @return  void
     *
     * @throws  UserBlockedException
     */
    protected function checkUserIsBlocked(User $user, array $credentials): void
    {
        if ((bool)$user->getAttribute('is_blocked')) {
            throw new UserBlockedException(
                trans('opx_users::auth.login_user_is_blocked'),
                [],
                $credentials,
                $this->codes['user_is_blocked']
            );
        }
    }

    /**
     * Check if user has verified email.
     *
     * @param User $user
     * @param array $credentials
     *
     * @return  void
     *
     * @throws  EmailNotConfirmedException
     */
    protected function checkEmailConfirmed(User $user, array $credentials): void
    {
        // if email confirmed, skip all other checks
        if ((bool)$user->getAttribute('is_email_confirmed')) {
            return;
        }

        $settings = OpxUsers::config('login_settings');

        $confirmationSent = false;

        // send confirmation email if it is not confirmed (disabled by default)
        if ($settings['send_confirmation_email'] ?? false) {

            $token = $this->makeEmailConfirmationToken($user);

            $this->sendEmailConfirmationToken($user, $token);

            $confirmationSent = true;
        }

        // check if login enabled with not confirmed email (enabled by default)
        if (!($settings['enable_not_confirmed_email'] ?? true)) {

            $message = trans('opx_users::auth.login_email_not_confirmed');

            if ($confirmationSent) {
                $message .= ' ' . trans('opx_users::auth.login_email_confirmation_sent');
            }

            throw new EmailNotConfirmedException(
                $message,
                [],
                $credentials,
                $this->codes['user_is_blocked']
            );
        }
    }

    /**
     * Check if user is activated.
     *
     * @param User $user
     * @param array $credentials
     *
     * @return  void
     *
     * @throws  UserNotActivatedException
     */
    protected function checkUserIsActivated(User $user, array $credentials): void
    {
        // if user's account active, skip all other checks
        if ((bool)$user->getAttribute('is_activated')) {
            return;
        }

        $settings = OpxUsers::config('login_settings');

        // if user account not active and not activated users login disabled (by default)
        if (!($settings['enable_not_activated'] ?? false)) {
            throw new UserNotActivatedException(
                trans('opx_users::auth.login_user_is_blocked'),
                [],
                $credentials,
                $this->codes['user_is_blocked']
            );
        }

        // otherwise, check for account activation on a login (by default is true)
        if ($settings['activate_on_login'] ?? true) {
            // will be saved later in loginUser() by $user->updateLastLogin()
            $user->setAttribute('is_activated', true);

            event(new UserActivated($user));
        }
    }

    /**
     * Attempt lo login user.
     *
     * @param User $user
     * @param bool $remember
     *
     * @return  void
     */
    protected function loginUser(User $user, bool $remember): void
    {
        Auth::guard('user')->login($user, $remember);

        $user->updateLastLogin();

        event(new UserAuthenticated($user));
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