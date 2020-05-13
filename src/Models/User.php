<?php

namespace Modules\Opx\Users\Models;

use Core\Foundation\Auth\Contracts\UserContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Modules\Opx\Users\Traits\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthContract;

class User extends Model implements UserContract, AuthContract
{
    use Authenticatable,
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
        $timestamp = $this->freshTimestamp();

        $this->setAttribute('last_login', $timestamp);
        $this->setAttribute('last_activity', $timestamp);

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

    public function details(): HasOne
    {
        return $this->hasOne(UserDetails::class);
    }
}
