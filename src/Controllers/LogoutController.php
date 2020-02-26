<?php

namespace Modules\Opx\User\Controllers;

use Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Opx\Users\Events\UserUnauthenticated;
use Modules\Opx\Users\Traits\Redirects;

class LogoutController extends Controller
{
    use Redirects;

    /**
     * Log the user out.
     *
     * @param Request $request
     *
     * @return  RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->performLogout($request);

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
        $this->performLogout($request);

        return response()->json([
            'message' => trans('opx_users::auth.logout_message'),
            'redirect' => $this->redirectTo('after_logout'),
        ]);
    }

    /**
     * Perform logout.
     *
     * @param Request $request
     *
     * @return  void
     */
    protected function performLogout(Request $request): void
    {
        $user = Auth::guard('user')->user();

        event(new UserUnauthenticated($user));

        Auth::guard('user')->logout();

        $request->session()->invalidate();
    }
}