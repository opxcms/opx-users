<?php

namespace Modules\Opx\Users\Controllers;

use Carbon\Carbon;
use Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Opx\Users\Exceptions\BaseUsersException;
use Modules\Opx\Users\Exceptions\InvalidCredentialsException;
use Modules\Opx\Users\Exceptions\ResetPasswordTokenThrottledException;
use Modules\Opx\Users\Exceptions\UserNotFoundException;
use Modules\Opx\Users\Models\User;
use Modules\Opx\Users\Notifications\PasswordResetNotification;
use Modules\Opx\Users\OpxUsers;
use Modules\Opx\Users\Traits\Credentials;
use Modules\Opx\Users\Traits\ResponseCodes;

class ForgotController extends Controller
{
    use Credentials,
        ResponseCodes;

    /**
     * Make password reset token and send email with link.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     */
    public function sendResetApi(Request $request): JsonResponse
    {
        try {
            $this->performSendReset($request);

        } catch (BaseUsersException $exception) {

            return response()->json($exception->toArray(), $exception->getCode());
        }

        return response()->json([
            'message' => trans('opx_users::auth.reset_link_sent'),
            'redirect' => route('opx_users::reset_form'),
            'token' => csrf_token(),
        ], $this->codes['success']);
    }

    /**
     * Perform token generation and sending link.
     *
     * @param Request $request
     *
     * @throws InvalidCredentialsException
     * @throws UserNotFoundException
     * @throws ResetPasswordTokenThrottledException
     */
    protected function performSendReset(Request $request): void
    {
        $credentials = $this->getValidatedCredentials($request, ['email']);

        // get user by email
        /** @var User $user */
        $user = User::query()->where('email', $credentials['email'])->first();
        if ($user === null) {
            throw new UserNotFoundException(
                '',
                ['email' => [trans('opx_users::auth.user_not_exists')]],
                $credentials,
                $this->codes['user_not_exists']
            );
        }

        // generate token
        $token = $this->makePasswordResetToken($user);

        // send token
        $this->sendEmailConfirmationToken($user, $token);

        // save message to session
        session()->put('message', trans('opx_users::auth.reset_link_sent'));
    }

    /**
     * Make token for mail confirmation.
     *
     * @param User $user
     *
     * @return  string
     *
     * @throws  ResetPasswordTokenThrottledException
     */
    protected function makePasswordResetToken(User $user): string
    {
        $userId = $user->getAttribute('id');

        // Check token already exists and throttle token generating.
        $oldToken = DB::table('users_password_resets')->where('user_id', $userId)->first();
        if ($oldToken !== null) {
            $releaseTime = Carbon::parse($oldToken->created_at)->addSeconds(OpxUsers::config('token_decay_seconds') ?? 60);
            $now = Carbon::now();
            if ($releaseTime > $now) {
                $seconds = $releaseTime->diffInSeconds($now);
                throw new ResetPasswordTokenThrottledException(
                    trans('opx_users::auth.reset_password_throttled', ['seconds' => $seconds]),
                    [],
                    [],
                    $this->codes['throttle_reset']
                );
            }
            // Delete all previous entries
            DB::table('users_password_resets')->where('user_id', $userId)->delete();
        }

        $token = str_random(32);

        $now = new Carbon;

        $ttl = (int)OpxUsers::config('reset_token_ttl');
        $expires = new Carbon;
        $expires->addMinutes($ttl ?? 60);

        DB::table('users_password_resets')->insert([
            'user_id' => $userId,
            'token' => $token,
            'created_at' => $now,
            'expires_at' => $expires,
        ]);

        return $token;
    }

    /**
     * Send email confirmation token
     *
     * @param User $user
     * @param string $token
     *
     * @return  void
     */
    protected function sendEmailConfirmationToken(User $user, string $token): void
    {
        $email = $user->getAttribute('email');

        Mail::send(new PasswordResetNotification($email, $token));
    }
}