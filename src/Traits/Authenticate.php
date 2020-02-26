<?php

namespace Modules\Opx\Users\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

trait Authenticate
{
    /**
     * Get credentials from request.
     *
     * @param Request $request
     * @param array $keys
     *
     * @return  array
     */
    protected function credentials(Request $request, array $keys = ['email', 'password', 'password_confirmation']): array
    {
        return $request->only($keys);
    }

    /**
     * Validate credentials.
     *
     * @param array $credentials
     *
     * @return  MessageBag|null
     */
    protected function validateCredentials($credentials): ?MessageBag
    {
        $validator = Validator::make(
            $credentials,
            $this->validationRules(),
            $this->validationMessages()
        );

        if ($validator->fails()) {
            return $validator->errors();
        }

        return null;
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
            'password' => 'required|string|min:6',
            'password_confirmation' => 'same:password',
        ];
    }

    /**
     * Credentials validation messages.
     *
     * @return  array
     */
    protected function validationMessages(): array
    {
        return [
            'email.required' => trans('opx_users::auth.email_required'),
            'email.email' => trans('opx_users::auth.email_email'),
            'password.required' => trans('opx_users::auth.password_required'),
            'password.string' => trans('opx_users::auth.password_string'),
            'password.min' => trans('opx_users::auth.password_min'),
            'password_confirmation.same' => trans('opx_users::auth.password_confirmation_same'),
        ];
    }
}