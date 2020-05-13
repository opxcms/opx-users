<?php

namespace Modules\Opx\Users\Controllers;

use Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Opx\Users\Events\UserUnauthenticated;
use Modules\Opx\Users\Exceptions\BaseUsersException;
use Modules\Opx\Users\Exceptions\UserNotLoggedInException;
use Modules\Opx\Users\Traits\Redirects;
use Modules\Opx\Users\Traits\ResponseCodes;

class LogoutController extends Controller
{
    use Redirects,
        ResponseCodes;

    /**
     * Log the user out.
     *
     * @param Request $request
     *
     * @return  RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        try {
            $this->performLogout($request);

        } catch (BaseUsersException $exception) {
            return back()->with(['message' => trans('opx_users::auth.logout_message')]);
        }

        return response()
            ->redirectTo($this->redirectTo('after_logout'))
            ->with(['message' => trans('opx_users::auth.logout_message')]);
    }

    /**
     * Log the user out through API.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     */
    public function logoutApi(Request $request): JsonResponse
    {
        try {
            $this->performLogout($request);

        } catch (BaseUsersException $exception) {

            return response()->json($exception->toArray(), $exception->getCode());
        }

        return response()->json([
            'message' => trans('opx_users::auth.logout_message'),
            'redirect' => $this->redirectTo('after_logout'),
            'token' => csrf_token(),
        ], $this->codes['success']);
    }

    /**
     * Perform logout.
     *
     * @param Request $request
     *
     * @return  void
     *
     * @throws UserNotLoggedInException
     */
    protected function performLogout(Request $request): void
    {
        if (!Auth::guard('user')->check()) {
            throw new UserNotLoggedInException(
                trans('opx_users::auth.user_not_logged_in'),
                [],
                [],
                $this->codes['not_logged_in']
            );
        }

        $user = Auth::guard('user')->user();

        event(new UserUnauthenticated($user));

        Auth::guard('user')->logout();
    }
}