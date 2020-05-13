<?php

namespace Modules\Opx\Users\Models;

use Core\Foundation\Auth\Contracts\UserContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Modules\Opx\Users\Traits\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthContract;

class UserDetails extends Model
{
    protected $table = 'users_details';

    public $timestamps = false;
}
