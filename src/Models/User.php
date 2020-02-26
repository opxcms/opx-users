<?php

namespace Modules\Opx\Users\Models;

use Core\Foundation\Auth\Contracts\UserContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Modules\Opx\Users\Traits\Authenticatable;
use Modules\Opx\Users\Traits\Authenticate;

class User extends Model implements UserContract
{
    use Authenticatable,
        Authenticate,
        Authorizable,
        SoftDeletes,
        Notifiable;

    /**
     * Update last login time.
     *
     * @param bool $save
     *
     * @return  void
     */
    public function updateLastLogin(bool $save = true): void
    {
        $this->setAttribute('last_login', $this->freshTimestamp());

        if ($save) {
            $this->save();
        }
    }

    /**
     * Update last activity time.
     *
     * @param bool $save
     *
     * @return  void
     */
    public function updateLastActivity(bool $save = true): void
    {
        $this->setAttribute('last_activity', $this->freshTimestamp());

        if ($save) {
            $this->save();
        }
    }
}
