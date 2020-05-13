<?php

namespace Modules\Opx\Users\Controllers;

use Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Opx\Users\Exceptions\BaseUsersException;
use Modules\Opx\Users\Exceptions\InvalidCredentialsException;
use Modules\Opx\Users\Exceptions\ResetTokenMismatchException;
use Modules\Opx\Users\Exceptions\UserNotFoundException;
use Modules\Opx\Users\Models\User;
use Modules\Opx\Users\Traits\Credentials;
use Modules\Opx\Users\Traits\Redirects;
use Modules\Opx\Users\Traits\ResetToken;
use Modules\Opx\Users\Traits\ResponseCodes;

class ResetController extends Controller
{
    use Redirects,
        ResponseCodes,
        ResetToken,
        Credentials;

    /**
     * Handel password reset request.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     */
    public function passwordResetApi(Request $request): JsonResponse
    {
        try {
            $this->performReset($request);

        } catch (BaseUsersException $exception) {

            return response()->json($exception->toArray(), $exception->getCode());
        }

        return response()->json([
            'message' => trans('opx_users::auth.reset_success'),
            'redirect' => $this->redirectTo('after_reset'),
            'token' => csrf_token(),
        ], $this->codes['success']);
    }

    /**
     * Perform password reset.
     *
     * @param Request $request
     *
     * @return  void
     *
     * @throws InvalidCredentialsException
     * @throws ResetTokenMismatchException
     * @throws UserNotFoundException
     */
    protected function performReset(Request $request): void
    {
        // Validate new password and confirmation
        $credentials = $this->getValidatedCredentials($request, ['email', 'token', 'password', 'password_confirmation']);

        // Check token
        if (!$this->isResetTokenValid($credentials['email'], $credentials['token'])) {
            throw new ResetTokenMismatchException(
                trans('opx_users::auth.reset_token_mismatch'),
                [],
                [],
                $this->codes['token_mismatch']
            );
        }

        // Change password

        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null) {
            throw new UserNotFoundException(
                trans(''),
                [],
                [],
                $this->codes['user_not_exists']
            );
        }

        $user->setAttribute('password', bcrypt($credentials['password']));
        $user->save();

        // Delete token record
        DB::table('users_password_resets')->where('user_id', $user->getAttribute('id'))->delete();
    }
}