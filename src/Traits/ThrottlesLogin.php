<?php

namespace Modules\Opx\Users\Traits;

use Illuminate\Cache\RateLimiter;
use Modules\Opx\Users\Events\Lockout;
use Modules\Opx\Users\OpxUsers;

trait ThrottlesLogin
{
    /**
     * Get the throttle key for the given credentials.
     *
     * @param array $credentials
     * @param string $ip
     *
     * @return  string
     */
    protected function throttleKey(array $credentials, string $ip): string
    {
        return mb_strtolower($credentials['email'], 'UTF-8') . '|' . $ip;
    }

    /**
     * Determine if the user has too many failed login attempts.
     *
     * @param array $credentials
     * @param string $ip
     *
     * @return  bool
     */
    protected function hasTooManyLoginAttempts(array $credentials, string $ip): bool
    {
        return $this->limiter()->tooManyAttempts(
            $this->throttleKey($credentials, $ip), $this->maxAttempts()
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
     * @param array $credentials
     * @param string $ip
     *
     * @return  void
     */
    protected function incrementLoginAttempts(array $credentials, string $ip): void
    {
        $this->limiter()->hit(
            $this->throttleKey($credentials, $ip), $this->decayMinutes()
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
     * @param array $credentials
     * @param string $ip
     *
     * @return  int
     */
    protected function lockoutSeconds(array $credentials, string $ip): int
    {
        return $this->limiter()->availableIn(
            $this->throttleKey($credentials, $ip)
        );
    }

    /**
     * Clear the login locks for the given user credentials.
     *
     * @param array $credentials
     * @param string $ip
     *
     * @return  void
     */
    protected function clearLoginAttempts(array $credentials, string $ip): void
    {
        $this->limiter()->clear($this->throttleKey($credentials, $ip));
    }

    /**
     * Fire an event when a lockout occurs.
     *
     * @param array $credentials
     * @param string $ip
     *
     * @return  void
     */
    protected function fireLockoutEvent(array $credentials, string $ip): void
    {
        event(new Lockout($credentials, $ip));
    }
}