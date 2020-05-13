<?php

namespace Modules\Opx\Users\Controllers;

use Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Opx\Users\Events\UserRegistered;
use Modules\Opx\Users\Exceptions\BaseUsersException;
use Modules\Opx\Users\Exceptions\EmailConfirmationTokenThrottledException;
use Modules\Opx\Users\Exceptions\EmailNotConfirmedException;
use Modules\Opx\Users\Exceptions\InvalidCredentialsException;
use Modules\Opx\Users\Exceptions\RegistrationIsDisabledException;
use Modules\Opx\Users\Exceptions\UserAlreadyExistsException;
use Modules\Opx\Users\Models\User;
use Modules\Opx\Users\Models\UserDetails;
use Modules\Opx\Users\OpxUsers;
use Modules\Opx\Users\Traits\Credentials;
use Modules\Opx\Users\Traits\EmailConfirmation;
use Modules\Opx\Users\Traits\Redirects;
use Modules\Opx\Users\Traits\ResponseCodes;

class RegisterController extends Controller
{
    use Credentials,
        Redirects,
        EmailConfirmation,
        ResponseCodes;

    public function registerApi(Request $request): JsonResponse
    {
        try {
            $this->performRegister($request);

        } catch (BaseUsersException $exception) {

            return response()->json($exception->toArray(), $exception->getCode());
        }

        return response()->json([
            'message' => trans('opx_users::auth.register_success'),
            'redirect' => $this->redirectTo('after_register'),
            'token' => csrf_token(),
        ], $this->codes['success']);
    }

    /**
     * Register user.
     *
     * @param Request $request
     *
     * @return  void
     *
     * @throws RegistrationIsDisabledException
     * @throws  InvalidCredentialsException
     * @throws UserAlreadyExistsException
     * @throws EmailNotConfirmedException
     */
    protected function performRegister(Request $request): void
    {
        $settings = OpxUsers::config('register_settings');

        if (!($settings['registration_enabled'] ?? false)) {
            throw new RegistrationIsDisabledException(
                '',
                [],
                [],
                $this->codes['registration_disabled']
            );
        }

        $credentials = $this->getValidatedCredentials($request, [
            'email', 'password', 'password_confirmation',
            'first_name', 'last_name', 'phone']);

        $matched = [];

        if ($this->isUserExists($credentials, $matched)) {
            throw new UserAlreadyExistsException(
                trans('opx_users::auth.user_already_exists'),
                $matched,
                $credentials,
                $this->codes['user_exists']
            );
        }

        $user = $this->registerUser($credentials);

        // Send email confirmation
        try {
            $token = $this->makeEmailConfirmationToken($user);
            $this->sendEmailConfirmationToken($user, $token);
            session()->put('message', trans('opx_users::auth.login_email_confirmation_sent'));
        } catch (EmailConfirmationTokenThrottledException $exception) {
            throw new EmailNotConfirmedException(
                $exception->getMessage(),
                [],
                $credentials,
                $this->codes['email_not_confirmed']
            );
        }
    }


    /**
     * Check is user exists.
     *
     * @param array $credentials
     * @param array $matched
     *
     * @return  bool
     */
    protected function isUserExists(array $credentials, array &$matched): bool
    {
        $sameEmail = User::query()->where('email', $credentials['email'])->count() !== 0;
        $samePhone = isset($credentials['phone']) ? (User::query()->where('phone', $credentials['phone'])->count() !== 0) : false;

        $matched = [];

        if ($sameEmail) {
            $matched['email'] = [trans('opx_users::auth.user_email_already_exists')];
        }

        if ($samePhone) {
            $matched['phone'] = [trans('opx_users::auth.user_phone_already_exists')];
        }

        return $sameEmail || $samePhone;
    }

    /**
     * Create new user.
     *
     * @param array $credentials
     *
     * @return  User
     */
    protected function registerUser(array $credentials): User
    {
        $user = new User;

        $user->setAttribute('email', $credentials['email']);
        $user->setAttribute('password', bcrypt($credentials['password']));
        $user->setAttribute('phone', $credentials['phone']);

        $user->save();

        if (isset($credentials['first_name']) || isset($credentials['middle_name']) || isset($credentials['last_name'])) {
            $details = new UserDetails();

            $details->setAttribute('user_id', $user->getAttribute('id'));
            $details->setAttribute('first_name', $credentials['first_name'] ?? null);
            $details->setAttribute('middle_name', $credentials['middle_name'] ?? null);
            $details->setAttribute('last_name', $credentials['last_name'] ?? null);

            $details->save();
        }

        event(new UserRegistered($user));

        return $user;
    }
}