@php
    $passwordLength = \Modules\Opx\Users\OpxUsers::config('min_password_length') ?? 6;
@endphp

@extends('site::layout')

@section('content')
    <div class="page-content">
        <div class="article">
            <div is="register-box" v-bind="{
                    title: '{{ trans('opx_users::forms.register_title') }}',
                    register_action_title: '{{ trans('opx_users::forms.register_action_title') }}',
                    email_title: '{{ trans('opx_users::forms.email_title') }}',
                    password_title: '{{ trans('opx_users::forms.password_title') }}',
                    password_confirm_title: '{{ trans('opx_users::forms.password_confirm_title') }}',
                    last_name_title: '{{ trans('opx_users::forms.last_name_title') }}',
                    first_name_title: '{{ trans('opx_users::forms.first_name_title') }}',
                    phone_title: '{{ trans('opx_users::forms.phone_title') }}',
                    error_phone_format: '{{ trans('opx_users::forms.error_phone_format') }}',
                    error_required: '{{ trans('opx_users::forms.error_required') }}',
                    error_password_length: '{{ trans('opx_users::forms.error_password_length', ['length' => $passwordLength]) }}',
                    error_password_match: '{{ trans('opx_users::forms.error_password_match') }}',
                    password_length: {{ $passwordLength }}
                    }">
            </div>
        </div>
    </div>

@endsection