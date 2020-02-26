<?php

namespace Modules\Opx\Users\Traits;

class Authorizable
{
    /**
     * Check if user has permission.
     *
     * @param string $permission
     *
     * @return  bool
     */
    public function can($permission): bool
    {
        return true;
    }
}