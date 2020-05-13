@php
    $passwordLength = \Modules\Opx\Users\OpxUsers::config('min_password_length') ?? 6;
@endphp

@extends('site::layout')

@section('content')
    <div class="page-content">
        <div class="article">
            <div is="login-box" v-bind="{
                    title: '{{ trans('opx_users::forms.login_title') }}',
                    email_title: '{{ trans('opx_users::forms.email_title') }}',
                    password_title: '{{ trans('opx_users::forms.password_title') }}',
                    login_action_title: '{{ trans('opx_users::forms.login_action_title') }}',
                    register_title: '{{ trans('opx_users::forms.register_title') }}',
                    remember_title: '{{ trans('opx_users::forms.remember_title') }}',
                    forgot_action_title: '{{ trans('opx_users::forms.forgot_title') }}',
                    error_required: '{{ trans('opx_users::forms.error_required') }}',
                    error_password_length: '{{ trans('opx_users::forms.error_password_length', ['length' => $passwordLength]) }}',
                    message: '{{ $message }}',
            @if($registrationEnabled)
                    registration_enabled: true,
            @else
                    registration_enabled: false,
            @endif
                    }">
            </div>
        </div>
    </div>
@endsection