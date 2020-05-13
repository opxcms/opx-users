<?php

namespace Modules\Opx\Users\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Modules\Opx\Users\Exceptions\InvalidCredentialsException;
use Modules\Opx\Users\OpxUsers;

trait Credentials
{
    /**
     * Get credentials from request and validate it.
     *
     * @param Request $request
     * @param array $keys
     *
     * @return  array
     *
     * @throws  InvalidCredentialsException
     */
    protected function getValidatedCredentials(Request $request, array $keys = ['email', 'password']): array
    {
        // Get credentials from request
        $credentials = $this->credentials($request, $keys);

        if(isset($credentials['phone'])){
            $credentials['phone'] = preg_replace('/\D/', '', $credentials['phone']);
        }

        // Validate credentials
        $errors = $this->validateCredentials($credentials, $keys);

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
     * Get credentials from request.
     *
     * @param Request $request
     * @param array $keys
     *
     * @return  array
     */
    protected function credentials(Request $request, array $keys = ['email', 'password']): array
    {
        return $request->only($keys);
    }

    /**
     * Validate credentials.
     *
     * @param array $credentials
     * @param array $keys
     *
     * @return  MessageBag|null
     */
    protected function validateCredentials(array $credentials, array $keys): ?MessageBag
    {
        $validator = Validator::make(
            $credentials,
            $this->validationRules($keys),
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
     * @param array $keys
     *
     * @return  array
     */
    protected function validationRules(array $keys): array
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required|string|min:' . (OpxUsers::config('min_password_length') ?? '6'),
            'password_confirmation' => 'same:password',
            'phone' => 'nullable|digits:11',
            'token' => 'required|string|size:32',
        ];

        return array_intersect_key($rules, array_flip($keys));
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
            'phone.digits' => trans('opx_users::auth.phone_digits'),
            'token.required' => trans('opx_users::auth.token_required'),
            'token.string' => trans('opx_users::auth.token_string'),
            'token.size' => trans('opx_users::auth.token_size'),
        ];
    }
}