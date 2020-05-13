<?php

namespace Modules\Opx\Users\Controllers;

use Core\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Opx\Users\OpxUsers;
use Modules\Opx\Users\Traits\ResetToken;

class FormsController extends Controller
{
    use ResetToken;

    /** @var bool */
    protected $registrationEnabled = false;

    /** @var string|null */
    protected $message;

    /**
     * FormsController constructor.
     */
    public function __construct()
    {
        $this->registrationEnabled = !empty(OpxUsers::config('register_settings')['registration_enabled']);
    }

    /**
     * Get message from session.
     *
     * @return  string|null
     */
    protected function getMessage(): ?string
    {
        if (session()->has('message')) {
            $this->message = session()->pull('message');
        }

        return $this->message;
    }

    /**
     * Make login form.
     *
     * @param Request $request
     *
     * @return  View
     */
    public function login(Request $request): View
    {
        return OpxUsers::view('login')->with(['message' => $this->getMessage(), 'registrationEnabled' => $this->registrationEnabled]);
    }

    /**
     * Make register form.
     *
     * @param Request $request
     *
     * @return  View
     */
    public function register(Request $request): View
    {
        if ($this->registrationEnabled === false) {
            abort(404);
        }

        return OpxUsers::view('register');
    }

    /**
     * Make forgot form.
     *
     * @param Request $request
     *
     * @return  View
     */
    public function forgot(Request $request): View
    {
        return OpxUsers::view('forgot')->with(['message' => $this->getMessage(), 'registrationEnabled' => $this->registrationEnabled]);
    }

    /**
     * Make reset password form.
     *
     * @param Request $request
     *
     * @return  View
     */
    public function reset(Request $request): View
    {
        $email = $request->input('email');
        $token = $request->input('token');
        $message = $this->getMessage();

        // Verify token and email or not empty message.
        if ((empty($message) && empty($email) && empty($token)) || ((!empty($email) || !empty($token)) && !$this->isResetTokenValid($email, $token))) {
            abort(404);
        }

        return OpxUsers::view('reset')->with(['email' => $email, 'token' => $token, 'message' => $message]);
    }
}