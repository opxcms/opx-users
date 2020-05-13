<?php

namespace Modules\Opx\Users\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Opx\Users\Exceptions\EmailConfirmationTokenThrottledException;
use Modules\Opx\Users\Models\User;
use Modules\Opx\Users\Notifications\EmailConfirmNotification;
use Modules\Opx\Users\OpxUsers;

trait EmailConfirmation
{
    /**
     * Make token for mail confirmation.
     *
     * @param User $user
     * @param string|null $email
     *
     * @return  string
     *
     * @throws  EmailConfirmationTokenThrottledException
     */
    protected function makeEmailConfirmationToken(User $user, ?string $email = null): string
    {
        $userId = $user->getAttribute('id');
        $email = $email ?? $user->getAttribute('email');

        // Check token already exists and throttle token generating.
        $oldToken = DB::table('users_email_confirmations')->where('user_id', $userId)->first();
        if ($oldToken !== null) {
            $releaseTime = Carbon::parse($oldToken->created_at)->addSeconds(OpxUsers::config('token_decay_seconds') ?? 60);
            $now = Carbon::now();
            if ($releaseTime > $now) {
                $seconds = $releaseTime->diffInSeconds($now);
                throw new EmailConfirmationTokenThrottledException(
                    trans('opx_users::auth.login_email_confirmation_throttled', ['seconds' => $seconds])
                );
            }
            // Delete all previous entries
            DB::table('users_email_confirmations')->where('user_id', $userId)->delete();
        }


        $token = str_random(32);

        $now = new Carbon;

        $ttl = (int)OpxUsers::config('email_confirm_token_ttl');
        $expires = new Carbon;
        $expires->addMinutes($ttl ?? 60);

        DB::table('users_email_confirmations')->insert([
            'user_id' => $userId,
            'email' => $email,
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
     * @param string|null $email
     *
     * @return  void
     */
    protected function sendEmailConfirmationToken(User $user, string $token, ?string $email = null): void
    {
        $email = $email ?? $user->getAttribute('email');

        Mail::send(new EmailConfirmNotification($email, $token));
    }
}