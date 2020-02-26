<?php

namespace Modules\Opx\Users\Traits;

use Modules\Opx\Users\OpxUsers;

trait Redirects
{
    /**
     * Get user redirect after action.
     *
     * @param string $action
     *
     * @return  string
     */
    protected function redirectTo($action): string
    {
        $redirect = OpxUsers::config('redirects')[$action] ?? '/';

        return session()->pull('url.intended', $redirect);
    }
}