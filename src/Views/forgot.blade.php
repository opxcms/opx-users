@extends('site::layout')

@section('content')
    <div class="page-content">
        <div class="article">
            <div is="forgot-box" v-bind="{
                    title: '{{ trans('opx_users::forms.forgot_title') }}',
                    email_title: '{{ trans('opx_users::forms.email_title') }}',
                    login_title: '{{ trans('opx_users::forms.login_title') }}',
                    register_title: '{{ trans('opx_users::forms.register_title') }}',
                    forgot_action_title: '{{ trans('opx_users::forms.forgot_action_title') }}',
                    error_required: '{{ trans('opx_users::forms.error_required') }}',
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