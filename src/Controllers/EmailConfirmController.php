<?php

namespace Modules\Opx\Users\Controllers;

use Carbon\Carbon;
use Core\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Opx\Users\Events\UserEmailChanged;
use Modules\Opx\Users\Events\UserEmailConfirmed;
use Modules\Opx\Users\Exceptions\BaseUsersException;
use Modules\Opx\Users\Exceptions\EmailConfirmationTokenExpiredException;
use Modules\Opx\Users\Exceptions\EmailConfirmationTokenMismatchException;
use Modules\Opx\Users\Exceptions\InvalidCredentialsException;
use Modules\Opx\Users\Models\User;
use Modules\Opx\Users\OpxUsers;
use Modules\Opx\Users\Traits\Credentials;
use Modules\Opx\Users\Traits\Redirects;
use Modules\Opx\Users\Traits\ResponseCodes;

class EmailConfirmController extends Controller
{
    use Credentials,
        Redirects,
        ResponseCodes;

    /**
     * Handle email confirmation http request.
     *
     * @param Request $request
     *
     * @return  View|RedirectResponse
     */
    public function confirm(Request $request)
    {
        try {
            $this->performEmailConfirm($request);

        } catch (BaseUsersException $exception) {

            return OpxUsers::view('message')->with([
                'error' => true,
                'message' => $exception->getMessage(),
            ]);
        }
        if (Auth::guard('user')->check()) {
            return OpxUsers::view('message')->with([
                'error' => false,
                'message' => trans('opx_users::auth.email_confirmed')
            ]);
        }

        return response()->redirectToRoute('opx_users::login_form')->with(['message' => trans('opx_users::auth.email_confirmed')]);
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
        $credentials = $this->getValidatedCredentials($request, ['email', 'token']);

        // get user assigned
        // throws EmailConfirmationTokenMismatchException or EmailConfirmationTokenExpiredException
        $user = $this->getUserToConfirm($credentials);

        $confirmingEmail = $credentials['email'];
        $currentEmail = $user->getAttribute('email');

        // set email confirmed
        if ((bool)$user->getAttribute('is_email_confirmed') === false) {
            $user->setAttribute('is_email_confirmed', true);
            event(new UserEmailConfirmed($user));
        }

        // set new email if it changed and fire event
        if ($confirmingEmail !== $currentEmail) {
            $user->setAttribute('email', $confirmingEmail);
            event(new UserEmailChanged($user, $currentEmail, $confirmingEmail));
        }

        // update activity (and save)
        $user->updateLastActivity();
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
}