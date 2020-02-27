<?php

namespace Modules\Opx\User\Controllers;

use Carbon\Carbon;
use Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Opx\Users\Events\UserEmailChanged;
use Modules\Opx\Users\Exceptions\BaseUsersException;
use Modules\Opx\Users\Exceptions\EmailConfirmationTokenExpiredException;
use Modules\Opx\Users\Exceptions\EmailConfirmationTokenMismatchException;
use Modules\Opx\Users\Exceptions\InvalidCredentialsException;
use Modules\Opx\Users\Models\User;
use Modules\Opx\Users\Traits\Credentials;
use Modules\Opx\Users\Traits\Redirects;

class EmailConfirmController extends Controller
{
    use Credentials,
        Redirects;

    protected $codes = [
        'success' => 200,
        'invalid_credentials' => 400,
        'token_mismatch' => 400,
        'token_expired' => 400,
    ];

    /**
     * Handle email confirmation http request.
     *
     * @param Request $request
     *
     * @return  RedirectResponse
     */
    public function confirm(Request $request): RedirectResponse
    {
        try {
            $this->performEmailConfirm($request);

        } catch (BaseUsersException $exception) {

            return back($exception->getCode())
                ->withInput($exception->getCredentials())
                ->withErrors($exception->getErrors())
                ->with(['message' => $exception->getMessage()]);
        }

        return response()->redirectTo(
            $this->redirectTo('email_confirmed'),
            $this->codes['success']
        )->with(['message' => trans('opx_users::auth.email_confirmed')]);
    }

    /**
     * Handle email confirmation API request.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     */
    public function confirmApi(Request $request): JsonResponse
    {
        try {
            $this->performEmailConfirm($request);

        } catch (BaseUsersException $exception) {

            return response()->json($exception->toArray(), $exception->getCode());
        }

        return response()->json([
            'message' => trans('opx_users::auth.email_confirmed'),
            'redirect' => $this->redirectTo('email_confirmed'),
        ], $this->codes['success']);
    }

    /**
     * Perform email confirmation or change.
     *
     * @param Request $request
     *
     * @return  void
     *
     * @throws  InvalidCredentialsException
     * @throws  EmailConfirmationTokenMismatchException
     * @throws  EmailConfirmationTokenExpiredException
     */
    protected function performEmailConfirm(Request $request): void
    {
        $credentials = $this->getValidatedCredentials($request);

        // get user assigned
        // throws EmailConfirmationTokenMismatchException or EmailConfirmationTokenExpiredException
        $user = $this->getUserToConfirm($credentials);

        $confirmingEmail = $credentials['email'];
        $currentEmail = $user->getAttribute('email');

        // set email confirmed
        $user->setAttribute('is_email_confirmed', true);

        // set new email if it changed and fire event
        if ($confirmingEmail !== $currentEmail) {
            $user->setAttribute('email', $confirmingEmail);
            event(new UserEmailChanged($user, $currentEmail, $confirmingEmail));
        }

        // update activity (and save)
        $user->updateLastActivity();
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
        $credentials = $this->credentials($request, ['email', 'token']);

        // Validate credentials
        $errors = $this->validateCredentials($credentials);

        if ($errors) {
            throw new InvalidCredentialsException(
                trans('opx_users::auth.email_confirm_validation_error'),
                $errors->messages(),
                $credentials,
                $this->codes['invalid_credentials']
            );
        }

        return $credentials;
    }

    /**
     * Get users_email_confirmations record by credentials.
     *
     * @param array $credentials
     *
     * @return  User
     *
     * @throws  EmailConfirmationTokenMismatchException
     * @throws EmailConfirmationTokenExpiredException
     */
    protected function getUserToConfirm(array $credentials): User
    {
        $tokenRecord = DB::table('users_email_confirmations')
            ->where('email', $credentials['email'])
            ->where('token', $credentials['token'])
            ->latest('created_at')
            ->first();

        if ($tokenRecord === null) {
            throw new EmailConfirmationTokenMismatchException(
                trans('opx_users::auth.email_confirm_token_mismatch'),
                [],
                $credentials,
                $this->codes['token_mismatch']
            );
        }

        $userId = $tokenRecord->user_id;

        // if token record found cleanup all other records
        DB::table('users_email_confirmations')->where('user_id', $userId)->delete();

        $expires = Carbon::parse($tokenRecord->expires_at);

        if ($expires < Carbon::now()) {
            throw new EmailConfirmationTokenExpiredException(
                trans('opx_users::auth.email_confirm_token_expired'),
                [],
                $credentials,
                $this->codes['token_expired']
            );
        }

        /** @var User $user */
        $user = User::query()->where('id', $userId)->first();

        return $user;
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
            'token' => 'required|string|size:32',
        ];
    }


}