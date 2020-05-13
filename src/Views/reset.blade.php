@php
    $passwordLength = \Modules\Opx\Users\OpxUsers::config('min_password_length') ?? 6;
@endphp

@extends('site::layout')

@section('content')
    @if($message)
        <div class="page-content">
            <div class="article">
                <p class="message">{{ $message }}</p>
            </div>
        </div>
    @else
        <div class="page-content">
            <div class="article">
                <div is="reset-box" v-bind="{
                        title: '{{ trans('opx_users::forms.reset_title') }}',
                        reset_action_title: '{{ trans('opx_users::forms.reset_action_title') }}',
                        password_title: '{{ trans('opx_users::forms.password_title') }}',
                        password_confirm_title: '{{ trans('opx_users::forms.password_confirm_title') }}',
                        error_required: '{{ trans('opx_users::forms.error_required') }}',
                        error_password_length: '{{ trans('opx_users::forms.error_password_length', ['length' => $passwordLength]) }}',
                        error_password_match: '{{ trans('opx_users::forms.error_password_match') }}',
                        password_length: {{ $passwordLength }},
                        email: '{{ $email }}',
                        token: '{{ $token }}',
                        }">
                </div>
            </div>
        </div>
    @endif
@endsection