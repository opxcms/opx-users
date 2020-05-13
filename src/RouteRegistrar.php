<?php

namespace Modules\Opx\Users;

use Core\Foundation\Module\BaseModule;
use Core\Foundation\Module\RouteRegistrar as BaseRouteRegistrar;
use Illuminate\Support\Facades\Route;

class RouteRegistrar extends BaseRouteRegistrar
{
    /** @var string Namespace of UserController */
    protected $userNamespace;

    /** @var string Namespace of controllers */
    protected $namespace;

    /** @var bool */
    protected $registrationEnabled;

    /**
     * RouteRegistrar constructor.
     *
     * @param BaseModule $module
     */
    public function __construct(BaseModule $module)
    {
        parent::__construct($module);

        $this->namespace = 'Modules\Opx\Users\Controllers\\';

        if (is_dir($module->templatePath('Controllers'))) {
            $this->userNamespace = 'Templates\Opx\Users\Controllers\\';
        } else {
            $this->userNamespace = 'Modules\Opx\Users\Controllers\\';
        }

        $settings = OpxUsers::config();

        $this->registrationEnabled = $settings['register_settings']['registration_enabled'] ?? false;
    }

    /**
     * Register public routes.
     *
     * @param string $profile
     *
     * @return  void
     */
    public function registerPublicRoutes($profile): void
    {
        // Login form
        Route::get('login', $this->namespace . 'FormsController@login')
            ->name('opx_users::login_form')
            ->middleware(['web', 'guest:user']);

        // Register form
        if ($this->registrationEnabled) {
            Route::get('register', $this->namespace . 'FormsController@register')
                ->name('opx_users::register_form')
                ->middleware(['web', 'guest:user']);
        }

        // Forgot form
        Route::get('forgot', $this->namespace . 'FormsController@forgot')
            ->name('opx_users::forgot_form')
            ->middleware(['web', 'guest:user']);

        // Reset password form
        // required parameters are `email` and `token`
        Route::get('reset', $this->namespace . 'FormsController@reset')
            ->name('opx_users::reset_form')
            ->middleware(['web', 'guest:user']);

        //
        // Controllers
        //

        // Login
        // required parameters are `email` and `password`. Optional `remember`
        // auth and guest guards protections must be handled in controller
        Route::post('login', $this->namespace . 'LoginController@login')
            ->name('opx_users::login')
            ->middleware(['web']);

        // Logout
        // no parameters
        Route::post('logout', $this->namespace . 'LogoutController@logout')
            ->name('opx_users::logout')
            ->middleware(['web', 'auth:user']);


        // Email confirmation
        // required parameters are `email` and `token`
        Route::get('email/confirm', $this->namespace . 'EmailConfirmController@confirm')
            ->name('opx_users::confirm_email')
            ->middleware('web');
    }

    /**
     * Register API routes.
     *
     * @param string $profile
     *
     * @return  void
     */
    public function registerPublicAPIRoutes($profile): void
    {
        // Get user details
        // required parameters are `email` and `password`. Optional `remember`
        Route::post('api/user/get', $this->userNamespace . 'UserController@getUser')
            ->name('opx_users::user_api')
            ->middleware(['web']);

        // Login
        // required parameters are `email` and `password`. Optional `remember`
        // auth and guest guards protections must be handled in controller
        Route::post('api/user/login', $this->namespace . 'LoginController@loginApi')
            ->name('opx_users::login_api')
            ->middleware(['web']);

        // Logout
        // no parameters
        // auth and guest guards protections must be handled in controller
        Route::post('api/user/logout', $this->namespace . 'LogoutController@logoutApi')
            ->name('opx_users::logout_api')
            ->middleware(['web']);

        // Registration
        // required parameters are `email`, `password` and `password_confirmation`
        if ($this->registrationEnabled) {
            Route::post('api/user/register', $this->namespace . 'RegisterController@registerApi')
                ->name('opx_users::register_api')
                ->middleware(['web', 'guest:user']);
        }

        // Send reset password token
        // required parameters is `email`
        Route::post('api/user/send_reset', $this->namespace . 'ForgotController@sendResetApi')
            ->name('opx_users::send_reset_api')
            ->middleware(['web', 'guest:user']);

        // Reset password
        Route::post('api/user/password_reset', $this->namespace . 'ResetController@passwordResetApi')
            ->name('opx_users::password_reset_api')
            ->middleware(['web', 'guest:user']);

    }

}