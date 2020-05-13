<?php

namespace Modules\Opx\Users\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Opx\Users\Models\User;

trait ResetToken
{
    /**
     * Check reset token valid.
     *
     * @param $email
     * @param $token
     *
     * @return  bool
     */
    protected function isResetTokenValid($email, $token): bool
    {
        if($email === null) {
            return false;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return false;
        }

        $record = DB::table('users_password_resets')->where('user_id', $user->getAttribute('id'))->first();

        if ($record === null){
            return false;
        }

        $now = new Carbon();

        if ($record->token !== $token || Carbon::parse($record->expires_at) < $now) {
            DB::table('users_password_resets')->where('user_id', $user->getAttribute('id'))->delete();
            return false;
        }

        return true;
    }
}