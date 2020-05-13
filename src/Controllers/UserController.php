<?php

namespace Modules\Opx\Users\Controllers;

use Core\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Opx\Users\Models\User;

class UserController extends Controller
{
    /**
     * Login user through regular http request.
     *
     * @param Request $request
     *
     * @return  JsonResponse
     */
    public function getUser(Request $request): JsonResponse
    {
        /** @var Guard $guard */
        $guard = Auth::guard('user');

        if (!$guard->check()) {
            return response()->json([
                'authenticated' => false,
                'token' => csrf_token(),
            ], 401);
        }

        /** @var User $user */
        $user = $guard->user();

        return response()->json([
            'authenticated' => true,
            'user' => ['id' => $user->getAttribute('id'), 'email' => $user->getAttribute('email')],
            'token' => csrf_token(),
        ]);
    }
}