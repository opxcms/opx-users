<?php

namespace Modules\Opx\User\Traits;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Modules\Opx\Users\Events\Lockout;
use Modules\Opx\Users\OpxUsers;

trait ThrottlesLogin
{
    /**
     * Determine if the user has too many failed login attempts.
     *
     * @param Request $request
     *
     * @return  bool
     */
    protected function hasTooManyLoginAttempts(Request $request): bool
    {
        return $this->limiter()->tooManyAttempts(
            $this->throttleKey($request), $this->maxAttempts()
        );
    }

    /**
     * Get the rate limiter instance.
     *
     * @return  RateLimiter
     */
    protected function limiter(): RateLimiter
    {
        return app(RateLimiter::class);
    }

    /**
     * Get the throttle key for the given request.
     *
     * @param Request $request
     *
     * @return  string
     */
    protected function throttleKey(Request $request): string
    {
        return mb_strtolower($request->input('email'), 'UTF-8') . '|' . $request->ip();
    }

    /**
     * Get the maximum number of attempts to allow.
     *
     * @return  int
     */
    public function maxAttempts(): int
    {
        $maxAttempts = OpxUsers::config('max_attempts');

        return ($maxAttempts === null) ? 5 : (int)$maxAttempts;
    }

    /**
     * Increment the login attempts for the user.
     *
     * @param Request $request
     *
     * @return  void
     */
    protected function incrementLoginAttempts(Request $request): void
    {
        $this->limiter()->hit(
            $this->throttleKey($request), $this->decayMinutes()
        );
    }

    /**
     * Get the number of minutes to throttle for.
     *
     * @return  int
     */
    public function decayMinutes(): int
    {
        $decayMinutes = OpxUsers::config('decay_minutes');

        return ($decayMinutes === null) ? 1 : (int)$decayMinutes;
    }

    /**
     * Make throttle seconds.
     *
     * @param Request $request
     *
     * @return  int
     */
    protected function lockoutSeconds(Request $request): int
    {
        return $this->limiter()->availableIn(
            $this->throttleKey($request)
        );
    }

    /**
     * Clear the login locks for the given user credentials.
     *
     * @param Request $request
     *
     * @return  void
     */
    protected function clearLoginAttempts(Request $request): void
    {
        $this->limiter()->clear($this->throttleKey($request));
    }

    /**
     * Fire an event when a lockout occurs.
     *
     * @param Request $request
     *
     * @return  void
     */
    protected function fireLockoutEvent(Request $request): void
    {
        event(new Lockout($request));
    }
}